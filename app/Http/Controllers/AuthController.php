<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Проверка прав администратора.
     */
    private function checkAdmin()
    {
        abort_unless(
            Auth::check() && Auth::user()->Role === 'admin',
            403,
            'Недостатньо прав для виконання цієї дії.'
        );
    }

    /**
     * Получить список подразделений.
     */
    private function getDepartments()
    {
        return DB::table('Підрозділи бригади')
            ->select('Код', 'Назва')
            ->orderBy('Назва')
            ->get();
    }

    /**
     * Показать страницу авторизации.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('mayno');
        }

        return view('auth.login');
    }

    /**
     * Проверить логин и пароль.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Введіть логін.',
            'password.required' => 'Введіть пароль.',
        ]);

        $remember = $request->boolean('remember');

        $success = Auth::attempt([
            'Login' => $credentials['login'],
            'password' => $credentials['password'],
            'IsActive' => 1,
        ], $remember);

        if (!$success) {
            return back()
                ->withErrors([
                    'login' => 'Невірний логін або пароль, або обліковий запис деактивовано.',
                ])
                ->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('mayno'));
    }

    /**
     * Выйти из системы.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Список пользователей.
     */
    public function index()
    {
        $this->checkAdmin();

        $users = User::query()
            ->orderBy('Login')
            ->get();

        $departmentRows = DB::table('UserDepartments')
            ->join(
                'Підрозділи бригади',
                'UserDepartments.DepartmentCode',
                '=',
                'Підрозділи бригади.Код'
            )
            ->select(
                'UserDepartments.UserId',
                'Підрозділи бригади.Назва'
            )
            ->orderBy('Підрозділи бригади.Назва')
            ->get()
            ->groupBy('UserId');

        foreach ($users as $user) {
            $user->departmentNames = ($departmentRows[$user->Id] ?? collect())
                ->pluck('Назва')
                ->implode(', ');
        }

        return view('users.index', compact('users'));
    }

    /**
     * Показать форму создания пользователя.
     */
    public function create()
    {
        $this->checkAdmin();

        $departments = $this->getDepartments();

        return view('auth.register', compact('departments'));
    }

    /**
     * Сохранить нового пользователя.
     */
    public function store(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'login' => [
                'required',
                'string',
                'max:100',
                'unique:Users,Login',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'required',
                'in:admin,editor,viewer',
            ],

            'DepartmentCodes' => [
                Rule::requiredIf(
                    in_array($request->input('role'), ['editor', 'viewer'], true)
                ),
                'nullable',
                'array',
            ],

            'DepartmentCodes.*' => [
                'integer',
                'distinct',
                'exists:Підрозділи бригади,Код',
            ],
        ], [
            'login.required' => 'Введіть логін.',
            'login.unique' => 'Такий логін вже існує.',

            'password.required' => 'Введіть пароль.',
            'password.min' => 'Пароль повинен містити мінімум 8 символів.',
            'password.confirmed' => 'Паролі не співпадають.',

            'role.required' => 'Оберіть роль користувача.',
            'role.in' => 'Невірна роль користувача.',

            'DepartmentCodes.required' => 'Оберіть хоча б один підрозділ.',
            'DepartmentCodes.array' => 'Невірний список підрозділів.',
            'DepartmentCodes.*.integer' => 'Невірний код підрозділу.',
            'DepartmentCodes.*.distinct' => 'Підрозділ не повинен повторюватися.',
            'DepartmentCodes.*.exists' => 'Один із вибраних підрозділів не існує.',
        ]);

        $departmentCodes = [];

        if (in_array($validated['role'], ['editor', 'viewer'], true)) {
            $departmentCodes = array_values(
                array_unique(
                    array_map('intval', $validated['DepartmentCodes'])
                )
            );
        }

        DB::transaction(function () use ($validated, $departmentCodes) {

            $user = User::create([
                'Login' => $validated['login'],
                'Password' => Hash::make($validated['password']),
                'Role' => $validated['role'],
                'IsActive' => 1,

                // Старое поле оставляем для совместимости.
                'DepartmentCode' => $departmentCodes[0] ?? null,
            ]);

            if (!empty($departmentCodes)) {
                DB::table('UserDepartments')->insert(
                    array_map(
                        fn ($departmentCode) => [
                            'UserId' => $user->Id,
                            'DepartmentCode' => $departmentCode,
                        ],
                        $departmentCodes
                    )
                );
            }
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Користувача успішно створено.');
    }

    /**
     * Показать форму редактирования пользователя.
     */
    public function edit($id)
    {
        $this->checkAdmin();

        $user = User::findOrFail($id);

        $departments = $this->getDepartments();

        $selectedDepartmentCodes = DB::table('UserDepartments')
            ->where('UserId', $user->Id)
            ->pluck('DepartmentCode')
            ->map(fn ($code) => (int) $code)
            ->toArray();

        return view(
            'users.edit',
            compact(
                'user',
                'departments',
                'selectedDepartmentCodes'
            )
        );
    }

    /**
     * Сохранить изменения пользователя.
     */
    public function update(Request $request, $id)
    {
        $this->checkAdmin();

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'login' => [
                'required',
                'string',
                'max:100',
                Rule::unique('Users', 'Login')->ignore($user->Id, 'Id'),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'required',
                'in:admin,editor,viewer',
            ],

            'IsActive' => [
                'required',
                'in:0,1',
            ],

            'DepartmentCodes' => [
                Rule::requiredIf(
                    in_array($request->input('role'), ['editor', 'viewer'], true)
                ),
                'nullable',
                'array',
            ],

            'DepartmentCodes.*' => [
                'integer',
                'distinct',
                'exists:Підрозділи бригади,Код',
            ],
        ], [
            'login.required' => 'Введіть логін.',
            'login.unique' => 'Такий логін вже існує.',

            'password.min' => 'Пароль повинен містити мінімум 8 символів.',
            'password.confirmed' => 'Паролі не співпадають.',

            'role.required' => 'Оберіть роль користувача.',
            'role.in' => 'Невірна роль користувача.',

            'IsActive.required' => 'Оберіть статус користувача.',
            'IsActive.in' => 'Невірний статус користувача.',

            'DepartmentCodes.required' => 'Оберіть хоча б один підрозділ.',
            'DepartmentCodes.array' => 'Невірний список підрозділів.',
            'DepartmentCodes.*.integer' => 'Невірний код підрозділу.',
            'DepartmentCodes.*.distinct' => 'Підрозділ не повинен повторюватися.',
            'DepartmentCodes.*.exists' => 'Один із вибраних підрозділів не існує.',
        ]);

        $departmentCodes = [];

        if (in_array($validated['role'], ['editor', 'viewer'], true)) {
            $departmentCodes = array_values(
                array_unique(
                    array_map('intval', $validated['DepartmentCodes'])
                )
            );
        }

        DB::transaction(function () use (
            $user,
            $validated,
            $departmentCodes
        ) {
            $user->Login = $validated['login'];
            $user->Role = $validated['role'];
            $user->IsActive = (int) $validated['IsActive'];

            // Пароль меняем только если поле заполнено.
            if (!empty($validated['password'])) {
                $user->Password = Hash::make($validated['password']);
            }

            // Старое поле оставляем для совместимости.
            $user->DepartmentCode = $departmentCodes[0] ?? null;

            $user->save();

            // Удаляем старые назначения.
            DB::table('UserDepartments')
                ->where('UserId', $user->Id)
                ->delete();

            // Записываем новый список подразделений.
            if (!empty($departmentCodes)) {
                DB::table('UserDepartments')->insert(
                    array_map(
                        fn ($departmentCode) => [
                            'UserId' => $user->Id,
                            'DepartmentCode' => $departmentCode,
                        ],
                        $departmentCodes
                    )
                );
            }
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Дані користувача успішно оновлено.');
    }

    /**
     * Удалить пользователя.
     */
    public function destroy($id)
    {
        $this->checkAdmin();

        $user = User::findOrFail($id);

        abort_if(
            (int) $user->Id === (int) Auth::id(),
            403,
            'Не можна видалити власний обліковий запис.'
        );

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Користувача успішно видалено.');
    }
}