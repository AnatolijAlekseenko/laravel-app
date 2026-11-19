<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MaynoExport;
use App\Models\User;

class BasicController extends Controller
{
    public function index()
    {
         return view('static.Start');
       # return view('static.Home');
    }

    public function about()
    {
        return view('static.About');
    }

    // ============================================================
    // ПОЛУЧЕНИЕ ПОДРАЗДЕЛЕНИЙ ПОЛЬЗОВАТЕЛЯ
    // ============================================================

    /**
     * Получить все назначенные пользователю подразделения.
     *
     * Старое поле DepartmentCode используется как резервный вариант
     * для совместимости со старыми учетными записями.
     */
    private function getUserDepartmentCodes(User $user): array
    {
        $departmentCodes = DB::table('UserDepartments')
            ->where('UserId', $user->Id)
            ->orderBy('DepartmentCode')
            ->pluck('DepartmentCode')
            ->map(fn ($code) => (int) $code)
            ->unique()
            ->values()
            ->toArray();

        if (empty($departmentCodes) && $user->DepartmentCode !== null) {
            $departmentCodes = [(int) $user->DepartmentCode];
        }

        abort_if(
            empty($departmentCodes),
            403,
            'Для користувача не призначено жодного підрозділу.'
        );

        return $departmentCodes;
    }

    /**
     * Построить SQL-условие IN для подразделений.
     *
     * Значения добавляются в bindings, а не вставляются в SQL.
     */
    private function departmentCondition(
        string $column,
        array $departmentCodes,
        array &$bindings
    ): string {
        $placeholders = implode(
            ', ',
            array_fill(0, count($departmentCodes), '?')
        );

        foreach ($departmentCodes as $departmentCode) {
            $bindings[] = (int) $departmentCode;
        }

        return "{$column} IN ({$placeholders})";
    }

    // ============================================================
    // АКТИВНЕ МАЙНО
    // ============================================================

    public function mayno(Request $request)
    {
        // ============================================================
        // 1. НАСТРОЙКИ
        // ============================================================

        $user = Auth::user();

        abort_unless($user, 401);

        $currentPage = max(1, (int)$request->input('page', 1));
        $perPage = 15;

        $whereClauses = [];
        $bindings = [];

        $departmentCodes = [];

        if ($user->Role !== 'admin') {
            $departmentCodes = $this->getUserDepartmentCodes($user);
        }

        // ============================================================
        // 2. ДИНАМИЧЕСКАЯ СБОРКА ФИЛЬТРОВ
        // ============================================================

        $allowedColumns = [
            'код' => 'ActiveMayno.Код',
            'серійнийномер' => 'ActiveMayno.[Серійний номер]',
            'назва' => 'ActiveMayno.Назва',
            'торговназва' => 'ActiveMayno.[Торгова назва]',
            'особливийоблік' => 'ActiveMayno.[Особливий облік]',
            'кількість' => 'ActiveMayno.Кількість',
            'категорія' => 'ActiveMayno.Категорія',
            'одиницівиміру' => 'ActiveMayno.[Одиниці виміру]',
            'ціна' => 'ActiveMayno.Ціна',
            'підрозділ' => 'ActiveMayno.Підрозділ',
            'кодпідрозділу' => 'ActiveMayno.Підрозділ',
            'назвапідрозділу' => 'POD.Назва',
            'видаткове' => 'ActiveMayno.Видаткове',
            'коментар' => 'ActiveMayno.Коментар',
            'знятозобліку' => 'ActiveMayno.[Знято з обліку]',
            'провірено' => 'ActiveMayno.Провірено',
            'видатковемайнокількість' => '(SELECT TOP 1 VZ.Кількість FROM #VidatkoveZalishkiNaPidrozdilah AS VZ WHERE VZ.Підрозділ IN (SELECT VZ2.Підрозділ FROM #VidatkoveZalishkiNaPidrozdilah AS VZ2 WHERE VZ2.Код = ActiveMayno.Код) AND VZ.Кількість = ActiveMayno.Кількість)',
            'видатковемайнонакладна' => '(SELECT TOP 1 VZOP.Код FROM #VidatkoveOtrymanePidrozdilami AS VZOP WHERE VZOP.[Код майна] = ActiveMayno.Код AND VZOP.Підрозділ = ActiveMayno.Підрозділ ORDER BY VZOP.Код DESC)',
            'видатковеномернакладної' => '(SELECT TOP 1 Номер FROM [dbo].[Внутрішні накладні] WHERE Код = (SELECT TOP 1 VZOP.Код FROM #VidatkoveOtrymanePidrozdilami AS VZOP WHERE VZOP.[Код майна] = ActiveMayno.Код AND VZOP.Підрозділ = ActiveMayno.Підрозділ ORDER BY VZOP.Код DESC))',
            'прийнявпіпвидаткове' => '(SELECT TOP 1 [Прийняв (ПІП)] FROM [dbo].[Внутрішні накладні] WHERE Код = (SELECT TOP 1 VZOP.Код FROM #VidatkoveOtrymanePidrozdilami AS VZOP WHERE VZOP.[Код майна] = ActiveMayno.Код AND VZOP.Підрозділ = ActiveMayno.Підрозділ ORDER BY VZOP.Код DESC))',
            'коднакладної' => '(SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (внутрішнє)] WHERE Майно = ActiveMayno.Код AND Кількість = ActiveMayno.Кількість ORDER BY Код DESC)',
            'номервнутрішньоїнакладної' => '(SELECT TOP 1 Номер FROM [dbo].[Внутрішні накладні] WHERE Код = (SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (внутрішнє)] WHERE Майно = ActiveMayno.Код AND Кількість = ActiveMayno.Кількість ORDER BY Код DESC))',
            'датавнутрішньоїнакладної' => '(SELECT TOP 1 FORMAT(Дата, \'dd.MM.yyyy\') FROM [dbo].[Внутрішні накладні] WHERE Код = (SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (внутрішнє)] WHERE Майно = ActiveMayno.Код AND Кількість = ActiveMayno.Кількість ORDER BY Код DESC))',
            'прийнявпіп' => '(SELECT TOP 1 [Прийняв (ПІП)] FROM [dbo].[Внутрішні накладні] WHERE Код = (SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (внутрішнє)] WHERE Майно = ActiveMayno.Код AND Кількість = ActiveMayno.Кількість ORDER BY Код DESC))',
            'юрнакладна' => '(SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (юр особи)] WHERE Майно = ActiveMayno.Код)',
            'номерюрнакладної' => '(SELECT Номер FROM [sklad].[dbo].[Накладні (юр особи)] WHERE Код = (SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (юр особи)] WHERE Майно = ActiveMayno.Код))',
            'датаюрнакладної' => '(SELECT FORMAT(Дата, \'dd.MM.yyyy\') FROM [sklad].[dbo].[Накладні (юр особи)] WHERE Код = (SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (юр особи)] WHERE Майно = ActiveMayno.Код))',
        ];

        foreach ($request->except(['page', 'export']) as $column => $value) {
            if (!is_scalar($value) || trim((string)$value) === '') {
                continue;
            }

            $normalizedColumn = preg_replace(
                '/[\s_]+/u',
                '',
                mb_strtolower((string)$column, 'UTF-8')
            );

            if (!isset($allowedColumns[$normalizedColumn])) {
                continue;
            }

            $whereClauses[] = "CAST({$allowedColumns[$normalizedColumn]} AS NVARCHAR(MAX)) COLLATE Cyrillic_General_CI_AI LIKE ?";
            $bindings[] = '%' . trim((string)$value) . '%';
        }

        // ============================================================
        // 3. ОПРЕДЕЛЯЕМ НАЛИЧИЕ ПОЛЬЗОВАТЕЛЬСКОГО ФИЛЬТРА
        // ============================================================

        $hasFilter = !empty($whereClauses);

        if ($hasFilter) {
            $currentPage = 1;
        }

        $whereSql = '';

        if ($hasFilter) {
            $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
        }

        // Ограничение по всем назначенным подразделениям.
        // $hasFilter не меняем, чтобы не нарушить пагинацию.

        if ($user->Role !== 'admin') {
            $departmentCondition = $this->departmentCondition(
                'ActiveMayno.Підрозділ',
                $departmentCodes,
                $bindings
            );

            $whereSql .= $hasFilter
                ? ' AND ' . $departmentCondition
                : 'WHERE ' . $departmentCondition;
        }

        // ============================================================
        // 4. СОЗДАНИЕ ВРЕМЕННЫХ ТАБЛИЦ
        // ============================================================

        DB::unprepared(<<<SQL

SET NOCOUNT ON;

-- ============================================================
-- ВИДАТКОВЕ ВЗЯТЕ НА БАЛАНС
-- ============================================================

IF OBJECT_ID('tempdb..#VidatkoveVzyateNaBalans') IS NOT NULL
    DROP TABLE #VidatkoveVzyateNaBalans;

SELECT
    Майно.Код AS [Код майна],
    Майно.Підрозділ,
    Майно.Кількість AS Переміщено
INTO #VidatkoveVzyateNaBalans
FROM Майно
WHERE
    Майно.Підрозділ = 2
    AND Майно.Видаткове = 1;

-- ============================================================
-- ВИДАТКОВЕ СПИСАНЕ З ПІДРОЗДІЛІВ
-- ============================================================

IF OBJECT_ID('tempdb..#VidatkoveSpisaneZPidrozdiliv') IS NOT NULL
    DROP TABLE #VidatkoveSpisaneZPidrozdiliv;

SELECT
    СписанняЧастокВидаткового.[Код майна],
    СписанняЧастокВидаткового.[Код підрозділу] AS Підрозділ,
    СписанняЧастокВидаткового.[Код документа],
    SUM(СписанняЧастокВидаткового.Кількість) AS Переміщено
INTO #VidatkoveSpisaneZPidrozdiliv
FROM СписанняЧастокВидаткового
GROUP BY
    СписанняЧастокВидаткового.[Код майна],
    СписанняЧастокВидаткового.[Код підрозділу],
    СписанняЧастокВидаткового.[Код документа];

-- ============================================================
-- ВИДАТКОВЕ ВИДАНЕ З ПІДРОЗДІЛІВ
-- ============================================================

IF OBJECT_ID('tempdb..#VidatkoveVidaneZPidrozdiliv') IS NOT NULL
    DROP TABLE #VidatkoveVidaneZPidrozdiliv;

SELECT
    Майно.Код AS [Код майна],
    [Внутрішні накладні].Відправник AS Підрозділ,
    SUM([Переміщення майна (внутрішнє)].Кількість) AS Переміщено
INTO #VidatkoveVidaneZPidrozdiliv
FROM
    [Внутрішні накладні]
INNER JOIN [Переміщення майна (внутрішнє)]
    ON [Внутрішні накладні].Код =
       [Переміщення майна (внутрішнє)].Накладна
INNER JOIN Майно
    ON [Переміщення майна (внутрішнє)].Майно = Майно.Код
GROUP BY
    Майно.Код,
    [Внутрішні накладні].Відправник,
    Майно.Видаткове
HAVING Майно.Видаткове = 1;

-- ============================================================
-- ВИДАТКОВЕ ОТРИМАНЕ ПІДРОЗДІЛАМИ
-- ============================================================

IF OBJECT_ID('tempdb..#VidatkoveOtrymanePidrozdilami') IS NOT NULL
    DROP TABLE #VidatkoveOtrymanePidrozdilami;

SELECT
    Майно.Код AS [Код майна],
    [Внутрішні накладні].Одержувач AS Підрозділ,
    SUM([Переміщення майна (внутрішнє)].Кількість) AS Переміщено,
    [Внутрішні накладні].Код
INTO #VidatkoveOtrymanePidrozdilami
FROM
    [Внутрішні накладні]
INNER JOIN [Переміщення майна (внутрішнє)]
    ON [Внутрішні накладні].Код =
       [Переміщення майна (внутрішнє)].Накладна
INNER JOIN Майно
    ON [Переміщення майна (внутрішнє)].Майно = Майно.Код
GROUP BY
    Майно.Код,
    [Внутрішні накладні].Одержувач,
    Майно.Видаткове,
    [Внутрішні накладні].Код,
    [Внутрішні накладні].Дата
HAVING Майно.Видаткове = 1;

-- ============================================================
-- ВИДАТКОВЕ ЗАЛИШКИ НА ПІДРОЗДІЛАХ
-- ============================================================

IF OBJECT_ID('tempdb..#VidatkoveZalishkiNaPidrozdilah') IS NOT NULL
    DROP TABLE #VidatkoveZalishkiNaPidrozdilah;

SELECT
    TMPTBL.[Код майна] AS Код,
    TMPTBL.Підрозділ,
    SUM(TMPTBL.Разом) AS Кількість
INTO #VidatkoveZalishkiNaPidrozdilah
FROM
(
    SELECT
        [Код майна],
        Підрозділ,
        Переміщено AS Разом
    FROM #VidatkoveOtrymanePidrozdilami

    UNION ALL

    SELECT
        [Код майна],
        Підрозділ,
        -1 * Переміщено AS Разом
    FROM #VidatkoveVidaneZPidrozdiliv

    UNION ALL

    SELECT
        [Код майна],
        Підрозділ,
        -1 * Переміщено AS Разом
    FROM #VidatkoveSpisaneZPidrozdiliv

    UNION ALL

    SELECT
        [Код майна],
        Підрозділ,
        Переміщено AS Разом
    FROM #VidatkoveVzyateNaBalans

) AS TMPTBL
GROUP BY
    TMPTBL.[Код майна],
    TMPTBL.Підрозділ;

SQL
        );

        // ============================================================
        // 5. БАЗОВЫЙ ЗАПРОС
        // ============================================================

        $sqlCoreSelect = <<<SQL

FROM
(
    -- ========================================================
    -- 5.1. НЕВИДАТКОВОЕ ИМУЩЕСТВО
    -- ========================================================

    SELECT
        Майно.Код,
        Майно.[Серійний номер],
        Майно.Назва,
        Майно.[Торгова назва],
        Майно.Категорія,
        Майно.[Одиниці виміру],
        Майно.Кількість,
        Майно.Ціна,
        Майно.Видаткове,
        Майно.Підрозділ,
        Майно.Коментар,
        Майно.[Особливий облік],
        Майно.[Знято з обліку],
        Майно.Провірено
    FROM Майно
    WHERE
        (
            Майно.Видаткове IS NULL
            OR Майно.Видаткове = 'False'
        )
        AND
        (
            Майно.[Знято з обліку] IS NULL
            OR Майно.[Знято з обліку] = 'False'
        )

    UNION ALL

    -- ========================================================
    -- 5.2. ВИДАТКОВОЕ ИМУЩЕСТВО НА ПОДРАЗДЕЛЕНИЯХ
    -- ========================================================

    SELECT
        Майно.Код,
        Майно.[Серійний номер],
        Майно.Назва,
        Майно.[Торгова назва],
        Майно.Категорія,
        Майно.[Одиниці виміру],
        VZNP.Кількість,
        Майно.Ціна,
        Майно.Видаткове,
        VZNP.Підрозділ,
        Майно.Коментар,
        Майно.[Особливий облік],
        Майно.[Знято з обліку],
        Майно.Провірено
    FROM #VidatkoveZalishkiNaPidrozdilah AS VZNP
    INNER JOIN Майно
        ON VZNP.Код = Майно.Код
    WHERE VZNP.Кількість <> 0

) AS ActiveMayno

LEFT JOIN [dbo].[Підрозділи бригади] AS POD
    ON POD.Код = ActiveMayno.Підрозділ

{$whereSql}

SQL;

        // ============================================================
        // 6. ПОДСЧЕТ КОЛИЧЕСТВА ПОСЛЕ ФИЛЬТРАЦИИ
        // ============================================================

        $countResult = DB::select(
            "SELECT COUNT(*) AS total {$sqlCoreSelect}",
            $bindings
        );

        $totalItems = (int)($countResult[0]->total ?? 0);
        $foundCount = $totalItems;

        // ============================================================
        // 7. КОЛОНКИ ДЛЯ ВЫВОДА
        // ============================================================

        $sqlDataColumns = <<<SQL

    ActiveMayno.Код,
    ActiveMayno.[Серійний номер],
    ActiveMayno.Назва,
    ActiveMayno.[Торгова назва] AS [Торгова назва],
    ActiveMayno.[Особливий облік],
    ActiveMayno.Кількість,
    ActiveMayno.Категорія,
    ActiveMayno.[Одиниці виміру] AS [Одиниці виміру],
    ActiveMayno.Ціна,
    ActiveMayno.Підрозділ,
    POD.Назва AS Назва_підрозділу,
    ActiveMayno.Видаткове,
    ActiveMayno.Коментар,
    ActiveMayno.[Знято з обліку] AS ЗнятоЗобліку,
    ActiveMayno.Провірено,

    (
        SELECT TOP 1 VZ.Кількість
        FROM #VidatkoveZalishkiNaPidrozdilah AS VZ
        WHERE
            VZ.Підрозділ IN
            (
                SELECT VZ2.Підрозділ
                FROM #VidatkoveZalishkiNaPidrozdilah AS VZ2
                WHERE VZ2.Код = ActiveMayno.Код
            )
            AND VZ.Кількість = ActiveMayno.Кількість
    ) AS Видаткове_Майно_Кількість,

    (
        SELECT TOP 1 VZOP.Код
        FROM #VidatkoveOtrymanePidrozdilami AS VZOP
        WHERE
            VZOP.[Код майна] = ActiveMayno.Код
            AND VZOP.Підрозділ = ActiveMayno.Підрозділ
        ORDER BY VZOP.Код DESC
    ) AS Видаткове_Майно_накладна,

    (
        SELECT TOP 1 Номер
        FROM [dbo].[Внутрішні накладні]
        WHERE Код =
        (
            SELECT TOP 1 VZOP.Код
            FROM #VidatkoveOtrymanePidrozdilami AS VZOP
            WHERE
                VZOP.[Код майна] = ActiveMayno.Код
                AND VZOP.Підрозділ = ActiveMayno.Підрозділ
            ORDER BY VZOP.Код DESC
        )
    ) AS Видаткове_НомерНакладної,

    (
        SELECT TOP 1 [Прийняв (ПІП)]
        FROM [dbo].[Внутрішні накладні]
        WHERE Код =
        (
            SELECT TOP 1 VZOP.Код
            FROM #VidatkoveOtrymanePidrozdilami AS VZOP
            WHERE
                VZOP.[Код майна] = ActiveMayno.Код
                AND VZOP.Підрозділ = ActiveMayno.Підрозділ
            ORDER BY VZOP.Код DESC
        )
    ) AS ПрийнявПІП_Видаткове,

    (
        SELECT TOP 1 Накладна
        FROM [dbo].[Переміщення майна (внутрішнє)]
        WHERE
            Майно = ActiveMayno.Код
            AND Кількість = ActiveMayno.Кількість
        ORDER BY Код DESC
    ) AS Код_накладної,

    (
        SELECT TOP 1 Номер
        FROM [dbo].[Внутрішні накладні]
        WHERE Код =
        (
            SELECT TOP 1 Накладна
            FROM [dbo].[Переміщення майна (внутрішнє)]
            WHERE
                Майно = ActiveMayno.Код
                AND Кількість = ActiveMayno.Кількість
            ORDER BY Код DESC
        )
    ) AS Номер_внутрішньої_накладної,

    (
        SELECT TOP 1 FORMAT(Дата, 'dd.MM.yyyy')
        FROM [dbo].[Внутрішні накладні]
        WHERE Код =
        (
            SELECT TOP 1 Накладна
            FROM [dbo].[Переміщення майна (внутрішнє)]
            WHERE
                Майно = ActiveMayno.Код
                AND Кількість = ActiveMayno.Кількість
            ORDER BY Код DESC
        )
    ) AS Дата_внутрішньої_накладної,

    (
        SELECT TOP 1 [Прийняв (ПІП)]
        FROM [dbo].[Внутрішні накладні]
        WHERE Код =
        (
            SELECT TOP 1 Накладна
            FROM [dbo].[Переміщення майна (внутрішнє)]
            WHERE
                Майно = ActiveMayno.Код
                AND Кількість = ActiveMayno.Кількість
            ORDER BY Код DESC
        )
    ) AS ПрийнявПІП,

    (
        SELECT TOP 1 Накладна
        FROM [dbo].[Переміщення майна (юр особи)]
        WHERE Майно = ActiveMayno.Код
    ) AS ЮрНакладна,

    (
        SELECT Номер
        FROM [sklad].[dbo].[Накладні (юр особи)]
        WHERE Код =
        (
            SELECT TOP 1 Накладна
            FROM [dbo].[Переміщення майна (юр особи)]
            WHERE Майно = ActiveMayno.Код
        )
    ) AS НомерЮрНакладної,

    (
        SELECT FORMAT(Дата, 'dd.MM.yyyy')
        FROM [sklad].[dbo].[Накладні (юр особи)]
        WHERE Код =
        (
            SELECT TOP 1 Накладна
            FROM [dbo].[Переміщення майна (юр особи)]
            WHERE Майно = ActiveMayno.Код
        )
    ) AS ДатаЮрНакладної

SQL;

        // ============================================================
        // 8. ПОЛУЧЕНИЕ ДАННЫХ
        // ============================================================

        if ($hasFilter || $request->boolean('export')) {

            $dataQuery = <<<SQL

SELECT
{$sqlDataColumns}

{$sqlCoreSelect}

ORDER BY
    ActiveMayno.Код,
    ActiveMayno.Підрозділ;

SQL;

            $dataResult = DB::select($dataQuery, $bindings);

            $perPage = max($totalItems, 1);

        } else {

            $offset = ($currentPage - 1) * $perPage;

            $dataQuery = <<<SQL

SELECT
{$sqlDataColumns}

{$sqlCoreSelect}

ORDER BY
    ActiveMayno.Код,
    ActiveMayno.Підрозділ

OFFSET {$offset} ROWS

FETCH NEXT {$perPage} ROWS ONLY;

SQL;

            $dataResult = DB::select($dataQuery, $bindings);
        }

        // ============================================================
        // 9. ФОРМИРОВАНИЕ НАЗВАНИЙ КОЛОНОК
        // ============================================================

        $columnNames = [];

        if (!empty($dataResult)) {

            $allFields = array_keys((array)$dataResult[0]);

            $columnNames = array_filter(
                $allFields,
                function ($field) {
                    return $field !== 'RowNum';
                }
            );

        } else {

            $columnNames = [
                'Код',
                'Серійний номер',
                'Назва',
                'Торгова назва',
                'Особливий облік',
                'Кількість',
                'Категорія',
                'Одиниці виміру',
                'Ціна',
                'Підрозділ',
                'Назва_підрозділу',
                'Видаткове',
                'Коментар',
                'ЗнятоЗобліку',
                'Провірено',
                'Видаткове_Майно_Кількість',
                'Видаткове_Майно_накладна',
                'Видаткове_НомерНакладної',
                'ПрийнявПІП_Видаткове',
                'Код_накладної',
                'Номер_внутрішньої_накладної',
                'Дата_внутрішньої_накладної',
                'ПрийнявПІП',
                'ЮрНакладна',
                'НомерЮрНакладної',
                'ДатаЮрНакладної'
            ];
        }

        // ============================================================
        // ЭКСПОРТ В EXCEL
        // ============================================================

        if ($request->boolean('export')) {

            ini_set('memory_limit', '1024M');
            set_time_limit(0);

            return Excel::download(
                new MaynoExport(
                    $dataResult,
                    array_values($columnNames),
                    'Активне майно'
                ),
                'Активне_майно_' . date('Y-m-d_H-i-s') . '.xlsx'
            );
        }

        // ============================================================
        // 10. ПАГИНАТОР LARAVEL
        // ============================================================

        $data = new \Illuminate\Pagination\LengthAwarePaginator(
            $dataResult,
            $totalItems,
            $perPage,
            $currentPage,
            [
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()
            ]
        );

        $data->withQueryString();

        // ============================================================
        // 11. ВОЗВРАЩАЕМ ПРЕДСТАВЛЕНИЕ
        // ============================================================

        return view(
            'static.mayno',
            compact(
                'data',
                'columnNames',
                'foundCount',
                'hasFilter'
            )
        );
    }

    // ============================================================
    // СПИСАНЕ МАЙНО
    // ============================================================

    public function maynoSpisane(Request $request)
    {
        // ============================================================
        // 1. НАСТРОЙКИ И ФИЛЬТРЫ
        // ============================================================

        $user = Auth::user();

        abort_unless($user, 401);

        $currentPage = max(1, (int)$request->input('page', 1));
        $perPage = 15;

        $whereClauses = [];
        $bindings = [];

        $departmentCodes = [];

        if ($user->Role !== 'admin') {
            $departmentCodes = $this->getUserDepartmentCodes($user);
        }

        $allowedColumns = [
            'код' => 'SpisaneMayno.Код',
            'серійнийномер' => 'SpisaneMayno.[Серійний номер]',
            'назва' => 'SpisaneMayno.Назва',
            'торговназва' => 'SpisaneMayno.[Торгова назва]',
            'кількість' => 'SpisaneMayno.Кількість',
            'категорія' => 'SpisaneMayno.Категорія',
            'одиницівиміру' => 'SpisaneMayno.[Одиниці виміру]',
            'ціна' => 'SpisaneMayno.Ціна',
            'видаткове' => 'SpisaneMayno.Видаткове',
            'кодпідрозділу' => 'SpisaneMayno.Підрозділ',
            'підрозділ' => 'SpisaneMayno.Підрозділ',
            'назвапідрозділу' => 'POD.Назва',
            'коментар' => 'SpisaneMayno.Коментар',
            'особливийоблік' => 'SpisaneMayno.[Особливий облік]',
            'знятозобліку' => 'SpisaneMayno.[Знято з обліку]',
            'провірено' => 'SpisaneMayno.Провірено',
            'коддокумента' => 'SpisaneMayno.[Код Документа]',
            'коднакладної' => '(SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (внутрішнє)] WHERE Майно = SpisaneMayno.Код AND Кількість = SpisaneMayno.Кількість ORDER BY Код DESC)',
            'номервнутрішньоїнакладної' => '(SELECT TOP 1 Номер FROM [dbo].[Внутрішні накладні] WHERE Код = (SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (внутрішнє)] WHERE Майно = SpisaneMayno.Код AND Кількість = SpisaneMayno.Кількість ORDER BY Код DESC))',
            'датавнутрішньоїнакладної' => '(SELECT TOP 1 FORMAT(Дата, \'dd.MM.yyyy\') FROM [dbo].[Внутрішні накладні] WHERE Код = (SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (внутрішнє)] WHERE Майно = SpisaneMayno.Код AND Кількість = SpisaneMayno.Кількість ORDER BY Код DESC))',
            'юрнакладна' => '(SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (юр особи)] WHERE Майно = SpisaneMayno.Код)',
            'номерюрнакладної' => '(SELECT Номер FROM [sklad].[dbo].[Накладні (юр особи)] WHERE Код = (SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (юр особи)] WHERE Майно = SpisaneMayno.Код))',
            'датаюрнакладної' => '(SELECT FORMAT(Дата, \'dd.MM.yyyy\') FROM [sklad].[dbo].[Накладні (юр особи)] WHERE Код = (SELECT TOP 1 Накладна FROM [dbo].[Переміщення майна (юр особи)] WHERE Майно = SpisaneMayno.Код))',
            'коддокументасписання' => 'SpisaneMayno.[Код Документа]',
            'номердокументасписання' => '(SELECT TOP 1 D.Номер FROM [sklad].[dbo].[Документи] AS D WHERE D.Код = SpisaneMayno.[Код Документа])',
            'датадокументасписання' => '(SELECT FORMAT(D.Дата, \'dd.MM.yyyy\') FROM [sklad].[dbo].[Документи] AS D WHERE D.Код = SpisaneMayno.[Код Документа])',
            'типдокументасписання' => '(SELECT TD.Назва FROM [sklad].[dbo].[Типи документів] AS TD WHERE TD.Код = (SELECT D.[Код типу документа] FROM [sklad].[dbo].[Документи] AS D WHERE D.Код = SpisaneMayno.[Код Документа]))',
        ];

        foreach ($request->except(['page', 'export']) as $column => $value) {
            if (!is_scalar($value) || trim((string)$value) === '') {
                continue;
            }

            $normalizedColumn = preg_replace(
                '/[\s_]+/u',
                '',
                mb_strtolower((string)$column, 'UTF-8')
            );

            if (!isset($allowedColumns[$normalizedColumn])) {
                continue;
            }

            $whereClauses[] = "CAST({$allowedColumns[$normalizedColumn]} AS NVARCHAR(MAX)) COLLATE Cyrillic_General_CI_AI LIKE ?";
            $bindings[] = '%' . trim((string)$value) . '%';
        }

        $hasFilter = !empty($whereClauses);

        if ($hasFilter) {
            $currentPage = 1;
        }

        $whereSql = '';

        if ($hasFilter) {
            $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
        }

        // Ограничение по всем назначенным подразделениям.
        if ($user->Role !== 'admin') {
            $departmentCondition = $this->departmentCondition(
                'SpisaneMayno.Підрозділ',
                $departmentCodes,
                $bindings
            );

            $whereSql .= $hasFilter
                ? ' AND ' . $departmentCondition
                : 'WHERE ' . $departmentCondition;
        }

        // ============================================================
        // 2. СОЗДАНИЕ ВРЕМЕННЫХ ТАБЛИЦ
        // ============================================================

        DB::unprepared(<<<SQL

SET NOCOUNT ON;

IF OBJECT_ID('tempdb..#ViborkaNeVidatkovogoSpisane') IS NOT NULL
    DROP TABLE #ViborkaNeVidatkovogoSpisane;

SELECT
    Майно.*,
    МайноІДокументи.КодДокумента AS [Код Документа]
INTO #ViborkaNeVidatkovogoSpisane
FROM Майно
INNER JOIN МайноІДокументи
    ON Майно.Код = МайноІДокументи.КодМайна
WHERE
    (
        Майно.Видаткове IS NULL
        OR Майно.Видаткове = 'False'
    );

IF OBJECT_ID('tempdb..#VidatkoveSpisaneZPidrozdiliv') IS NOT NULL
    DROP TABLE #VidatkoveSpisaneZPidrozdiliv;

SELECT
    СписанняЧастокВидаткового.[Код майна],
    СписанняЧастокВидаткового.[Код підрозділу] AS Підрозділ,
    СписанняЧастокВидаткового.[Код документа],
    SUM(СписанняЧастокВидаткового.Кількість) AS Переміщено
INTO #VidatkoveSpisaneZPidrozdiliv
FROM СписанняЧастокВидаткового
GROUP BY
    СписанняЧастокВидаткового.[Код майна],
    СписанняЧастокВидаткового.[Код підрозділу],
    СписанняЧастокВидаткового.[Код документа];

SQL
        );

        // ============================================================
        // 3. БАЗОВЫЙ ЗАПРОС
        // ============================================================

        $sqlCoreSelect = <<<SQL

FROM
(
    SELECT
        V.Код,
        V.[Серійний номер],
        V.Назва,
        V.[Торгова назва],
        V.Категорія,
        V.[Одиниці виміру],
        V.Кількість,
        V.Ціна,
        V.Видаткове,
        V.Підрозділ,
        V.Коментар,
        V.[Кількість для переміщення],
        V.[Особливий облік],
        V.[Знято з обліку],
        V.Створено,
        V.Stamp,
        V.NSN,
        V.Провірено,
        V.[Код Документа],
        V.ЦінаЛеся
    FROM #ViborkaNeVidatkovogoSpisane AS V

    UNION ALL

    SELECT
        Майно.Код,
        Майно.[Серійний номер],
        Майно.Назва,
        Майно.[Торгова назва],
        Майно.Категорія,
        Майно.[Одиниці виміру],
        vdszp.Переміщено AS Кількість,
        Майно.Ціна,
        Майно.Видаткове,
        vdszp.Підрозділ,
        Майно.Коментар,
        Майно.[Кількість для переміщення],
        Майно.[Особливий облік],
        Майно.[Знято з обліку],
        Майно.Створено,
        Майно.Stamp,
        Майно.NSN,
        Майно.Провірено,
        vdszp.[Код документа] AS [Код Документа],
        Майно.ЦінаЛеся
    FROM #VidatkoveSpisaneZPidrozdiliv AS vdszp
    INNER JOIN Майно
        ON vdszp.[Код майна] = Майно.Код
    WHERE vdszp.Переміщено <> 0

) AS SpisaneMayno

LEFT JOIN [dbo].[Підрозділи бригади] POD
    ON POD.Код = SpisaneMayno.Підрозділ

{$whereSql}

SQL;

        // ============================================================
        // 4. ПОДСЧЕТ КОЛИЧЕСТВА ПОСЛЕ ФИЛЬТРАЦИИ
        // ============================================================

        $countResult = DB::select(
            "SELECT COUNT(*) AS total {$sqlCoreSelect}",
            $bindings
        );

        $totalItems = (int)($countResult[0]->total ?? 0);
        $foundCount = $totalItems;

        // ============================================================
        // 5. ПОЛЯ ТАБЛИЦЫ
        // ============================================================

        $sqlDataColumns = <<<SQL

    SpisaneMayno.Код,
    SpisaneMayno.[Серійний номер],
    SpisaneMayno.Назва,
    SpisaneMayno.Кількість,
    SpisaneMayno.Категорія,
    SpisaneMayno.[Одиниці виміру],
    SpisaneMayno.Ціна,
    SpisaneMayno.Видаткове,
    SpisaneMayno.Підрозділ AS КодПідрозділу,
    POD.Назва AS Назва_підрозділу,
    SpisaneMayno.Коментар,
    SpisaneMayno.[Особливий облік],
    SpisaneMayno.[Знято з обліку] AS ЗнятоЗобліку,
    SpisaneMayno.Провірено,

    (
        SELECT TOP 1 Накладна
        FROM [dbo].[Переміщення майна (внутрішнє)]
        WHERE
            Майно = SpisaneMayno.Код
            AND Кількість = SpisaneMayno.Кількість
        ORDER BY Код DESC
    ) AS Код_накладної,

    (
        SELECT TOP 1 Номер
        FROM [dbo].[Внутрішні накладні]
        WHERE Код =
        (
            SELECT TOP 1 Накладна
            FROM [dbo].[Переміщення майна (внутрішнє)]
            WHERE
                Майно = SpisaneMayno.Код
                AND Кількість = SpisaneMayno.Кількість
            ORDER BY Код DESC
        )
    ) AS Номер_внутрішньої_накладної,

    (
        SELECT TOP 1 FORMAT(Дата, 'dd.MM.yyyy')
        FROM [dbo].[Внутрішні накладні]
        WHERE Код =
        (
            SELECT TOP 1 Накладна
            FROM [dbo].[Переміщення майна (внутрішнє)]
            WHERE
                Майно = SpisaneMayno.Код
                AND Кількість = SpisaneMayno.Кількість
            ORDER BY Код DESC
        )
    ) AS Дата_внутрішньої_накладної,

    (
        SELECT TOP 1 Накладна
        FROM [dbo].[Переміщення майна (юр особи)]
        WHERE Майно = SpisaneMayno.Код
    ) AS ЮрНакладна,

    (
        SELECT Номер
        FROM [sklad].[dbo].[Накладні (юр особи)]
        WHERE Код =
        (
            SELECT TOP 1 Накладна
            FROM [dbo].[Переміщення майна (юр особи)]
            WHERE Майно = SpisaneMayno.Код
        )
    ) AS НомерЮрНакладної,

    (
        SELECT FORMAT(Дата, 'dd.MM.yyyy')
        FROM [sklad].[dbo].[Накладні (юр особи)]
        WHERE Код =
        (
            SELECT TOP 1 Накладна
            FROM [dbo].[Переміщення майна (юр особи)]
            WHERE Майно = SpisaneMayno.Код
        )
    ) AS ДатаЮрНакладної,

    (
        SELECT TOP 1 MID.КодДокумента
        FROM [dbo].[МайноІДокументи] AS MID
        WHERE
            MID.КодМайна = SpisaneMayno.Код
            AND MID.КодДокумента = SpisaneMayno.[Код Документа]
    ) AS Код_Документа_Списання,

    (
        SELECT TOP 1 D.Номер
        FROM [sklad].[dbo].[Документи] AS D
        WHERE D.Код = SpisaneMayno.[Код Документа]
    ) AS Номер_Документа_Списання,

    (
        SELECT FORMAT(D.Дата, 'dd.MM.yyyy')
        FROM [sklad].[dbo].[Документи] AS D
        WHERE D.Код = SpisaneMayno.[Код Документа]
    ) AS Дата_Документа_Списання,

    (
        SELECT TD.Назва
        FROM [sklad].[dbo].[Типи документів] AS TD
        WHERE TD.Код =
        (
            SELECT D.[Код типу документа]
            FROM [sklad].[dbo].[Документи] AS D
            WHERE D.Код = SpisaneMayno.[Код Документа]
        )
    ) AS ТипДокументаСписання

SQL;

        // ============================================================
        // 6. ПОЛУЧЕНИЕ ДАННЫХ
        // ============================================================

        if ($hasFilter || $request->boolean('export')) {

            $dataQuery = <<<SQL

SELECT
{$sqlDataColumns}

{$sqlCoreSelect}

ORDER BY
    SpisaneMayno.Код,
    SpisaneMayno.[Код Документа];

SQL;

            $dataResult = DB::select($dataQuery, $bindings);

            $perPage = max($totalItems, 1);

        } else {

            $offset = ($currentPage - 1) * $perPage;

            $dataQuery = <<<SQL

SELECT
{$sqlDataColumns}

{$sqlCoreSelect}

ORDER BY
    SpisaneMayno.Код,
    SpisaneMayno.[Код Документа]

OFFSET {$offset} ROWS

FETCH NEXT {$perPage} ROWS ONLY;

SQL;

            $dataResult = DB::select($dataQuery, $bindings);
        }

        // ============================================================
        // 7. НАЗВАНИЯ КОЛОНОК
        // ============================================================

        $columnNames = [];

        if (!empty($dataResult)) {

            $allFields = array_keys((array)$dataResult[0]);

            $columnNames = array_filter(
                $allFields,
                function ($field) {
                    return $field !== 'RowNum';
                }
            );

        } else {

            $columnNames = [
                'Код',
                'Серійний номер',
                'Назва',
                'Кількість',
                'Категорія',
                'Одиниці виміру',
                'Ціна',
                'Видаткове',
                'КодПідрозділу',
                'Назва_підрозділу',
                'Коментар',
                'Особливий облік',
                'ЗнятоЗобліку',
                'Провірено',
                'Код_накладної',
                'Номер_внутрішньої_накладної',
                'Дата_внутрішньої_накладної',
                'ЮрНакладна',
                'НомерЮрНакладної',
                'ДатаЮрНакладної',
                'Код_Документа_Списання',
                'Номер_Документа_Списання',
                'Дата_Документа_Списання',
                'ТипДокументаСписання'
            ];
        }

        // ============================================================
        // ЭКСПОРТ В EXCEL
        // ============================================================

        if ($request->boolean('export')) {

            ini_set('memory_limit', '1024M');
            set_time_limit(0);

            return Excel::download(
                new MaynoExport(
                    $dataResult,
                    array_values($columnNames),
                    'Списане майно'
                ),
                'Списане_майно_' . date('Y-m-d_H-i-s') . '.xlsx'
            );
        }

        // ============================================================
        // 8. ПАГИНАТОР
        // ============================================================

        $data = new \Illuminate\Pagination\LengthAwarePaginator(
            $dataResult,
            $totalItems,
            $perPage,
            $currentPage,
            [
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()
            ]
        );

        $data->withQueryString();

        // ============================================================
        // 9. ПЕРЕДАЧА ДАННЫХ В BLADE
        // ============================================================

        return view(
            'static.maynoSpisane',
            compact(
                'data',
                'columnNames',
                'foundCount',
                'hasFilter'
            )
        );
    }

    // ============================================================
    // РЕДАГУВАННЯ МАЙНА
    // Зміни записуються ВИКЛЮЧНО в таблицю [dbo].[Майно]
    // ============================================================

    /**
     * Только просмотр записи.
     */
    public function ViewOnlyMayno($code)
    {
        $user = Auth::user();

        abort_unless($user, 401);

        if ($user->Role !== 'admin') {

            $departmentCodes = $this->getUserDepartmentCodes($user);

            $bindings = [$code];

            $departmentCondition = $this->departmentCondition(
                '[Підрозділ]',
                $departmentCodes,
                $bindings
            );

            $row = DB::selectOne(
                "SELECT
                    [Код],
                    [Серійний номер],
                    [Назва],
                    [Кількість],
                    [Підрозділ],
                    [Ціна],
                    [Коментар]
                 FROM [dbo].[Майно]
                 WHERE [Код] = ?
                   AND {$departmentCondition}",
                $bindings
            );

        } else {

            $row = DB::selectOne(
                'SELECT
                    [Код],
                    [Серійний номер],
                    [Назва],
                    [Кількість],
                    [Підрозділ],
                    [Ціна],
                    [Коментар]
                 FROM [dbo].[Майно]
                 WHERE [Код] = ?',
                [$code]
            );
        }

        if (!$row) {
            return response()->json([
                'message' => 'Запис у таблиці Майно не знайдено.'
            ], 404);
        }

        return response()->json($row);
    }

    /**
     * Получение записи для окна редактирования/просмотра.
     *
     * Viewer может открыть запись, но не может её сохранить.
     */
    public function editMayno($code)
    {
        $user = Auth::user();

        abort_unless($user, 401);

        if ($user->Role !== 'admin') {

            $departmentCodes = $this->getUserDepartmentCodes($user);

            $bindings = [$code];

            $departmentCondition = $this->departmentCondition(
                'M.[Підрозділ]',
                $departmentCodes,
                $bindings
            );

            $row = DB::selectOne(
                "SELECT
                    M.[Код],
                    M.[Серійний номер],
                    M.[Назва],
                    M.[Кількість],
                    M.[Підрозділ],
                    POD.[Назва] AS [Назва_підрозділу],
                    M.[Ціна],
                    M.[Коментар]
                 FROM [dbo].[Майно] AS M
                 LEFT JOIN [dbo].[Підрозділи бригади] AS POD
                    ON POD.[Код] = M.[Підрозділ]
                 WHERE M.[Код] = ?
                   AND {$departmentCondition}",
                $bindings
            );

        } else {

            $row = DB::selectOne(
                'SELECT
                    M.[Код],
                    M.[Серійний номер],
                    M.[Назва],
                    M.[Кількість],
                    M.[Підрозділ],
                    POD.[Назва] AS [Назва_підрозділу],
                    M.[Ціна],
                    M.[Коментар]
                 FROM [dbo].[Майно] AS M
                 LEFT JOIN [dbo].[Підрозділи бригади] AS POD
                    ON POD.[Код] = M.[Підрозділ]
                 WHERE M.[Код] = ?',
                [$code]
            );
        }

        if (!$row) {
            return response()->json([
                'message' => 'Запис у таблиці Майно не знайдено.'
            ], 404);
        }

        return response()->json($row);
    }

    /**
     * Обновление данных в базе.
     */
    public function updateMayno(Request $request, $code)
    {
        $user = Auth::user();

        abort_unless($user, 401);

        // Проверка прав доступа.
        abort_unless(
            in_array($user->Role, ['admin', 'editor'], true),
            403,
            'У вас немає прав для редагування майна.'
        );

        $departmentCodes = [];

        if ($user->Role === 'editor') {
            $departmentCodes = $this->getUserDepartmentCodes($user);
        }

        $validated = $request->validate([
            'serijnyi_nomer' => ['nullable', 'string', 'max:255'],
            'nazva'          => ['nullable', 'string', 'max:1000'],
            'kilkist'        => ['required', 'numeric', 'min:0'],
            'pidrozdil'      => ['nullable', 'integer'],
            'cina'           => ['nullable', 'numeric'],
            'komentar'       => ['nullable', 'string', 'max:4000'],
        ], [
            'kilkist.required' => 'Вкажіть кількість.',
            'kilkist.numeric' => 'Кількість повинна бути числом.',
            'pidrozdil.integer' => 'Код підрозділу повинен бути цілим числом.',
            'cina.numeric' => 'Ціна повинна бути числом.',
        ]);

        if ($user->Role === 'editor') {

            // Проверяем исходную запись и получаем её подразделение.
            $bindings = [$code];

            $departmentCondition = $this->departmentCondition(
                '[Підрозділ]',
                $departmentCodes,
                $bindings
            );

            $exists = DB::selectOne(
                "SELECT [Код], [Підрозділ]
                 FROM [dbo].[Майно]
                 WHERE [Код] = ?
                   AND {$departmentCondition}",
                $bindings
            );

            if (!$exists) {
                return response()->json([
                    'message' => 'Запис не знайдено або він належить іншому підрозділу.'
                ], 404);
            }

            // Редактор не может переместить имущество в другое подразделение.
            // Сохраняем исходное подразделение записи.
            $validated['pidrozdil'] = (int) $exists->Підрозділ;

            $updateBindings = [
                $validated['serijnyi_nomer'] ?? null,
                $validated['nazva'] ?? null,
                $validated['kilkist'],
                $validated['pidrozdil'],
                $validated['cina'] ?? null,
                $validated['komentar'] ?? null,
                $code,
            ];

            $updateDepartmentCondition = $this->departmentCondition(
                '[Підрозділ]',
                $departmentCodes,
                $updateBindings
            );

            DB::update(
                "UPDATE [dbo].[Майно]
                 SET [Серійний номер] = ?,
                     [Назва] = ?,
                     [Кількість] = ?,
                     [Підрозділ] = ?,
                     [Ціна] = ?,
                     [Коментар] = ?
                 WHERE [Код] = ?
                   AND {$updateDepartmentCondition}",
                $updateBindings
            );

        } else {

            // Администратор может редактировать имущество любого подразделения.
            $exists = DB::selectOne(
                'SELECT [Код]
                 FROM [dbo].[Майно]
                 WHERE [Код] = ?',
                [$code]
            );

            if (!$exists) {
                return response()->json([
                    'message' => 'Запис у таблиці Майно не знайдено.'
                ], 404);
            }

            DB::update(
                'UPDATE [dbo].[Майно]
                 SET [Серійний номер] = ?,
                     [Назва] = ?,
                     [Кількість] = ?,
                     [Підрозділ] = ?,
                     [Ціна] = ?,
                     [Коментар] = ?
                 WHERE [Код] = ?',
                [
                    $validated['serijnyi_nomer'] ?? null,
                    $validated['nazva'] ?? null,
                    $validated['kilkist'],
                    $validated['pidrozdil'] ?? null,
                    $validated['cina'] ?? null,
                    $validated['komentar'] ?? null,
                    $code,
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Зміни успішно збережено в таблиці Майно.',
        ]);
    }

    /**
     * Просмотр списанного имущества.
     *
     * Доступ только к исходным записям назначенных подразделений
     * для editor и viewer.
     */
    public function editMaynoSpisane($code)
    {
        $user = Auth::user();

        abort_unless($user, 401);

        if ($user->Role !== 'admin') {

            $departmentCodes = $this->getUserDepartmentCodes($user);

            $bindings = [$code];

            $departmentCondition = $this->departmentCondition(
                '[Підрозділ]',
                $departmentCodes,
                $bindings
            );

            $row = DB::selectOne(
                "SELECT
                    [Код],
                    [Серійний номер],
                    [Назва],
                    [Кількість],
                    [Підрозділ],
                    [Ціна],
                    [Коментар]
                 FROM [dbo].[Майно]
                 WHERE [Код] = ?
                   AND {$departmentCondition}",
                $bindings
            );

        } else {

            $row = DB::selectOne(
                'SELECT
                    [Код],
                    [Серійний номер],
                    [Назва],
                    [Кількість],
                    [Підрозділ],
                    [Ціна],
                    [Коментар]
                 FROM [dbo].[Майно]
                 WHERE [Код] = ?',
                [$code]
            );
        }

        if (!$row) {
            return response()->json([
                'message' => 'Запис у таблиці Майно не знайдено.'
            ], 404);
        }

        return response()->json($row);
    }
}