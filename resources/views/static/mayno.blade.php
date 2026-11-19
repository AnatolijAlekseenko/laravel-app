
@extends('layouts.main')

@section('header-title')
    Активне майно
@endsection

@section('content')

<h1>Активне майно</h1>

<form method="GET" action="{{ route('mayno') }}">
    <div class="table-container">
        <table class="mayno-table">
            <thead>
                <tr>
                    @foreach($columnNames as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($columnNames as $column)
                        <th>
                            <input
                                type="text"
                                name="{{ $column }}"
                                value="{{ request()->query($column) }}"
                                placeholder="Пошук..."
                            >
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse($data as $row)
                
                    <tr data-code="{{ $row->Код ?? '' }}"
                    class="{{ 
                   
                    (
            (($row->Видаткове ?? 0) == 1)
                ? 'expense-row'
                : ''
        ) .
        (
            (($row->{'Особливий облік'} ?? 0) == 1)
                ? ' MaynoOsobiste-expense-row'
                : ''
        )
                    }}"


                    title="Двічі клацніть, щоб редагувати запис">
                        @foreach($columnNames as $column)
                            <td>{{ $row->{$column} ?? '' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columnNames) }}" class="empty-message">
                            Записів не знайдено
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-actions">
        <a class="btn-excel"
           href="{{ route('mayno', array_merge(request()->except(['page', 'export']), ['export' => 1])) }}">
            📊 Вивантажити в Excel
        </a>

        <button type="submit" class="btn-search">Знайти</button>

        <a href="{{ route('mayno') }}" class="btn-reset">Очистити фільтр</a>
    </div>
</form>

<div class="table-footer">
    <div>
        Всього знайдено записів:
        <strong>{{ $data->total() }}</strong>
    </div>
</div>

<div class="pagination-container">
    {{ $data->links('pagination::simple-bootstrap-4', [
        'Previous' => '← Назад',
        'Next' => 'Далі →'
    ]) }}
</div>

<!-- Модальное окно -->
<div class="edit-modal" id="editModal">
    <div class="edit-modal-content">
        <h2>Редагування активного майна</h2>
        <p class="edit-hint">Зміни зберігаються безпосередньо в таблицю «Майно».</p>

        <form id="editMaynoForm">
            <div class="edit-form-grid">

                <div class="edit-field full-width">
                    <label for="edit_serijnyi_nomer">Серійний номер</label>
                    <input type="text" id="edit_serijnyi_nomer" name="serijnyi_nomer">
                </div>

                <div class="edit-field full-width">
                    <label for="edit_nazva">Назва</label>
                    <input type="text" id="edit_nazva" name="nazva">
                </div>

                <div class="edit-field">
                    <label for="edit_kilkist">Кількість</label>
                    <input type="number" id="edit_kilkist" name="kilkist" min="0" step="any" required>
                </div>

             

                <div class="edit-field">
                    <label for="editDepartmentName">Назва підрозділу</label>
                    <input type="text" id="editDepartmentName" readonly>
                </div>

                <div class="edit-field">
                    <label for="edit_cina">Ціна</label>
                    <input type="number" id="edit_cina" name="cina" step="any">
                </div>

                <div class="edit-field full-width">
                    <label for="edit_komentar">Коментар</label>
                    <textarea id="edit_komentar" name="komentar"></textarea>
                </div>

            </div>

            <div class="edit-error" id="editError"></div>

            <div class="edit-modal-buttons">
                <button type="button" class="btn-cancel" id="cancelEdit">Скасувати</button>
                <button type="submit" class="btn-save" id="saveEdit">Зберегти</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // Проверка роли текущего пользователя
    const userRole = @json(Auth::user()->Role);

    // Право на редактирование
    const canEdit = ['admin', 'editor'].includes(userRole);

    const modal = document.getElementById('editModal');
    const form = document.getElementById('editMaynoForm');
    const errorBox = document.getElementById('editError');
    const saveButton = document.getElementById('saveEdit');
    // Для пользователя viewer запрещаем редактирование
    if (!canEdit) {

        // Запрещаем изменение всех полей формы
        form.querySelectorAll('input, textarea').forEach(function (field) {
            field.disabled = true;
        });

        // Скрываем кнопку сохранения
        saveButton.style.display = 'none';
    }
    // Маршруты редактирования активного имущества
    const editUrlTemplate = @json(route('mayno.edit', ['code' => '__CODE__']));
    const updateUrlTemplate = @json(route('mayno.update', ['code' => '__CODE__']));

    const csrfToken = @json(csrf_token());

    let currentCode = null;

    function makeUrl(template, code) {
        return template.replace('__CODE__', encodeURIComponent(code));
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.style.display = 'block';
    }

    function hideError() {
        errorBox.textContent = '';
        errorBox.style.display = 'none';
    }

    function closeModal() {
        modal.classList.remove('show');
        currentCode = null;
        hideError();
    }

    async function openEditModal(code) {

        if (!code) {
            alert('Не вдалося визначити Код запису.');
            return;
        }

        currentCode = code;
        hideError();
        form.reset();
        // Повторно устанавливаем ограничения в зависимости от роли
        form.querySelectorAll('input, textarea').forEach(function (field) {
        field.disabled = !canEdit;
        });

saveButton.style.display = canEdit ? '' : 'none';


        modal.classList.add('show');

        try {
            const response = await fetch(makeUrl(editUrlTemplate, code), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Не вдалося завантажити запис.');
            }

            document.getElementById('edit_serijnyi_nomer').value =
                data['Серійний номер'] ?? '';

            document.getElementById('edit_nazva').value =
                data['Назва'] ?? '';

            document.getElementById('edit_kilkist').value =
                data['Кількість'] ?? '';

         

            // Название подразделения, полученное из контроллера
            document.getElementById('editDepartmentName').value =
                data['Назва_підрозділу'] ?? '';

            document.getElementById('edit_cina').value =
                data['Ціна'] ?? '';

            document.getElementById('edit_komentar').value =
                data['Коментар'] ?? '';

        } catch (error) {
            showError(error.message);
        }
    }

// Открытие карточки двойным щелчком по строке таблицы
document.querySelectorAll('.mayno-table tbody tr[data-code]').forEach(function (row) {

    row.addEventListener('dblclick', function () {
        openEditModal(this.dataset.code);
    });

});

    // Закрытие карточки
    document.getElementById('cancelEdit').addEventListener('click', closeModal);

   //modal.addEventListener('click', function (event) {
   //      if (event.target === modal) {
   //         closeModal();
   //     }
    //});

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });

    // Сохранение изменений
    form.addEventListener('submit', async function (event) {

        event.preventDefault();
        hideError();

        if (!currentCode) {
            showError('Не визначено Код запису.');
            return;
        }

        const payload = {
            serijnyi_nomer: document.getElementById('edit_serijnyi_nomer').value,
            nazva: document.getElementById('edit_nazva').value,
            kilkist: document.getElementById('edit_kilkist').value,
            pidrozdil: document.getElementById('edit_pidrozdil').value || null,
            cina: document.getElementById('edit_cina').value || null,
            komentar: document.getElementById('edit_komentar').value
        };

        saveButton.disabled = true;
        saveButton.textContent = 'Збереження...';

        try {

            const response = await fetch(makeUrl(updateUrlTemplate, currentCode), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) {

                let message = data.message || 'Помилка під час збереження.';

                if (data.errors) {
                    message += '\n' + Object.values(data.errors).flat().join('\n');
                }

                throw new Error(message);
            }

            alert(data.message || 'Зміни успішно збережено.');
            window.location.reload();

        } catch (error) {
            showError(error.message);

        } finally {
            saveButton.disabled = false;
            saveButton.textContent = 'Зберегти';
        }

    });

});

function exportTableToExcel(tableClass, fileName) {

    const table = document.querySelector('.' + tableClass);

    if (!table) {
        alert('Таблицю не знайдено!');
        return;
    }

    const csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {

        const cells = row.querySelectorAll('th, td');
        const rowData = [];

        cells.forEach(cell => {

            const clone = cell.cloneNode(true);

            clone.querySelectorAll('input, button, select, textarea')
                .forEach(element => element.remove());

            let value = clone.innerText.replace(/\s+/g, ' ').trim();
            value = value.replace(/"/g, '""');

            rowData.push('"' + value + '"');

        });

        csv.push(rowData.join(';'));

    });

    const content = '\uFEFF' + csv.join('\r\n');
    const blob = new Blob([content], {
        type: 'text/csv;charset=utf-8;'
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = fileName + '.csv';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    URL.revokeObjectURL(url);
}
</script>

@endsection
