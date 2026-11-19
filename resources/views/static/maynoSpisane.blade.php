@extends('layouts.main')

@section('header-title')
    Списане майно
@endsection

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="mayno-page">
    <h1>Списане майно</h1>
    <div class="edit-hint">Для просмоту запису двічі натисніть на потрібний рядок таблиці.</div>

    <form method="GET" action="{{ route('maynoSpisane') }}">
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
                                <input type="text" name="{{ $column }}" value="{{ request()->query($column) }}" placeholder="Пошук...">
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $row)
                        <tr class="editable-row" data-code="{{ $row->Код }}" title="Двічі натисніть для редагування">
                            @foreach($columnNames as $column)
                                <td>{{ $row->{$column} ?? '' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columnNames) }}" class="empty-message">Записей не найдено</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-actions">
            <a class="btn-excel" href="{{ route('maynoSpisane', array_merge(request()->except(['page', 'export']), ['export' => 1])) }}">📊 Вивантажити в Excel</a>
            <button type="submit" class="btn-search">Знайти</button>
            <a href="{{ route('maynoSpisane') }}" class="btn-reset">Очистити фільтр</a>
        </div>
    </form>

    <div class="table-footer">Всього знайдено записів: <strong>{{ $data->total() }}</strong></div>
    <div class="pagination-container">
        {{ $data->links('pagination::simple-bootstrap-4', ['previous' => '← Назад', 'next' => 'Далі →']) }}
    </div>
</div>

{{-- Модальне вікно розташоване поза GET-формою, щоб форми не вкладалися одна в одну. --}}
<div id="editMaynoModal" aria-hidden="true">
    <div class="edit-modal-card" role="dialog" aria-modal="true" aria-labelledby="editMaynoTitle">
        <h2 id="editMaynoTitle">Просмотр майна</h2>
        <div id="editMaynoCode" style="color:#aaa;margin-bottom:14px"></div>
        <div class="edit-form-grid">
            <div class="edit-field full">
                <label for="editSerial">Серійний номер</label>
                <input id="editSerial" type="text">
            </div>
            <div class="edit-field full">
                <label for="editName">Назва</label>
                <input id="editName" type="text">
            </div>
            <div class="edit-field">
                <label for="editQuantity">Кількість</label>
                <input id="editQuantity" type="number" min="0" step="any" required>
            </div>
            <div class="edit-field">
                <label for="editDepartment">Код підрозділу</label>
                <input id="editDepartment" type="number" step="1">
            </div>
            <div class="edit-field">
                <label for="editPrice">Ціна</label>
                <input id="editPrice" type="number" step="any">
            </div>
            <div class="edit-field full">
                <label for="editComment">Коментар</label>
                <textarea id="editComment"></textarea>
            </div>
        </div>
        <div id="editMaynoError"></div>
        <div id="editMaynoSuccess"></div>
        <div class="edit-modal-actions">
            <button type="button" id="cancelMaynoBtn">Закрити просмотр</button>
       
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('editMaynoModal');

  const editUrlTemplate = @json(route('maynoSpisane.edit', ['code' => '__CODE__']));

    let currentCode = null;

    const fields = {
        serial: document.getElementById('editSerial'),
        name: document.getElementById('editName'),
        quantity: document.getElementById('editQuantity'),
        department: document.getElementById('editDepartment'),
        price: document.getElementById('editPrice'),
        comment: document.getElementById('editComment')
    };

    const errorBox = document.getElementById('editMaynoError');
    const successBox = document.getElementById('editMaynoSuccess');

    // Все поля доступны только для чтения.
    Object.values(fields).forEach(field => {
        field.readOnly = true;
        field.style.background = '#303030';
        field.style.cursor = 'default';
    });

    function showError(message) {
        errorBox.textContent = message;
        errorBox.style.display = 'block';
        successBox.style.display = 'none';
    }

    function closeModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        currentCode = null;
    }

    async function openEditor(code) {
        currentCode = code;

        errorBox.style.display = 'none';
        successBox.style.display = 'none';

        // Очищаем старые значения.
        Object.values(fields).forEach(field => {
            field.value = '';
        });

        document.getElementById('editMaynoCode').textContent =
            'Код запису: ' + code;

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');

        try {
            const url = editUrlTemplate.replace(
                '__CODE__',
                encodeURIComponent(code)
            );

            console.log('Запрос записи:', url);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const responseText = await response.text();

            console.log('Ответ сервера:', responseText);

            let data;

            try {
                data = JSON.parse(responseText);
            } catch {
                throw new Error(
                    'Сервер вернул не JSON. Проверь маршрут и контроллер. Ответ: ' +
                    responseText.substring(0, 300)
                );
            }

            if (!response.ok) {
                throw new Error(
                    'Ошибка сервера (' + response.status + '): ' +
                    (data.message || responseText)
                );
            }

            // Поддерживаем как обычный JSON, так и ответ с обёрткой data.
            if (data.data && typeof data.data === 'object') {
                data = data.data;
            }

            if (!data || typeof data !== 'object') {
                throw new Error('Сервер не вернул данные записи.');
            }

            // Заполняем поля.
            fields.serial.value = data['Серійний номер'] ?? '';
            fields.name.value = data['Назва'] ?? '';
            fields.quantity.value = data['Кількість'] ?? '';
            fields.department.value = data['Підрозділ'] ?? '';
            fields.price.value = data['Ціна'] ?? '';
            fields.comment.value = data['Коментар'] ?? '';

            console.log('Поля формы заполнены:', data);

        } catch (error) {
            console.error('Ошибка загрузки записи:', error);
            showError(error.message);
        }
    }

    // Двойной щелчок по строке.
    document.querySelectorAll('.mayno-table tbody tr[data-code]')
        .forEach(row => {
            row.addEventListener('dblclick', () => {
                openEditor(row.dataset.code);
            });
        });

    // Закрытие окна.
    document.getElementById('cancelMaynoBtn')
        .addEventListener('click', closeModal);

   // modal.addEventListener('click', event => {
   //     if (event.target === modal) {
   //         closeModal();
   //     }
   // });

    document.addEventListener('keydown', event => {
        if (
            event.key === 'Escape' &&
            modal.classList.contains('show')
        ) {
            closeModal();
        }
    });
})();
</script>


@endsection
