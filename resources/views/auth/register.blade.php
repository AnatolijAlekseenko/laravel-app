<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Створення користувача</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #171717;
            color: #eeeeee;
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .register-container {
            width: 100%;
            max-width: 500px;
            padding: 30px;
            background: #252525;
            border: 1px solid #444;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,.5);
        }

        h2 {
            color: #ff9800;
            text-align: center;
            margin-top: 0;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            background: #171717;
            color: white;
            border: 1px solid #555;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #ff9800;
        }

        select option {
            background: #171717;
            color: white;
        }

        button {
            width: 100%;
            padding: 13px;
            background: #ff9800;
            color: #171717;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #ffad33;
        }

        .success {
            padding: 12px;
            margin-bottom: 20px;
            background: #21452c;
            color: #a8e6b4;
            border-radius: 6px;
        }

        .error {
            padding: 12px;
            margin-bottom: 20px;
            background: #542727;
            color: #ffbaba;
            border-radius: 6px;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #ff9800;
            text-decoration: none;
        }

        .back:hover {
            text-decoration: underline;
        }

        .department-wrapper.hidden {
            display: none;
        }

        .department-list {
            max-height: 260px;
            overflow-y: auto;
            padding: 10px 12px;
            margin-bottom: 20px;
            background: #171717;
            border: 1px solid #555;
            border-radius: 6px;
        }

        .department-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            margin: 0;
            cursor: pointer;
        }

        .department-item input {
            width: 18px;
            height: 18px;
            margin: 0;
            accent-color: #ff9800;
            cursor: pointer;
        }

        .department-item span {
            font-size: 14px;
        }

        .hint {
            color: #aaa;
            font-size: 12px;
            margin-top: -12px;
            margin-bottom: 12px;
        }
    </style>
</head>

<body>

<div class="register-container">

    <h2>Створення користувача</h2>

    @if (session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <label for="login">Логін</label>

        <input
            type="text"
            id="login"
            name="login"
            value="{{ old('login') }}"
            maxlength="100"
            autocomplete="off"
            required
        >

        <label for="password">Пароль</label>

        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <label for="password_confirmation">Повторіть пароль</label>

        <input
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <label for="role">Роль користувача</label>

        <select id="role" name="role" required>
            <option value="viewer" {{ old('role', 'viewer') === 'viewer' ? 'selected' : '' }}>
                Перегляд (viewer)
            </option>

            <option value="editor" {{ old('role') === 'editor' ? 'selected' : '' }}>
                Редагування (editor)
            </option>

            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>
                Адміністратор (admin)
            </option>
        </select>

        <div id="department-wrapper" class="department-wrapper">

            <label>Підрозділи користувача</label>

            <div class="hint">
                Можна обрати декілька підрозділів.
            </div>

            <div class="department-list">

                @foreach ($departments as $department)

                    <label class="department-item">
                        <input
                            type="checkbox"
                            name="DepartmentCodes[]"
                            value="{{ $department->Код }}"
                            {{ in_array((string)$department->Код, array_map('strval', old('DepartmentCodes', []))) ? 'checked' : '' }}
                        >

                        <span>{{ $department->Назва }}</span>
                    </label>

                @endforeach

            </div>
        </div>

        <button type="submit">
            Створити користувача
        </button>

    </form>

    <a href="{{ route('users.index') }}" class="back">
        До управління користувачами
    </a>

    <a href="{{ route('mayno') }}" class="back">
        Повернутися до системи
    </a>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const roleSelect = document.getElementById('role');
    const departmentWrapper = document.getElementById('department-wrapper');
    const departmentCheckboxes = document.querySelectorAll(
        'input[name="DepartmentCodes[]"]'
    );

    function updateDepartmentVisibility() {

        const isAdmin = roleSelect.value === 'admin';

        departmentWrapper.classList.toggle('hidden', isAdmin);

        departmentCheckboxes.forEach(function (checkbox) {
            checkbox.disabled = isAdmin;
            checkbox.required = false;
        });

        if (isAdmin) {
            departmentCheckboxes.forEach(function (checkbox) {
                checkbox.checked = false;
            });
        }
    }

    roleSelect.addEventListener('change', updateDepartmentVisibility);

    updateDepartmentVisibility();

});
</script>

</body>
</html>