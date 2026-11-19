<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Управління користувачами</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 30px;
            background: #171717;
            color: #eee;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 25px;
            background: #252525;
            border: 1px solid #444;
            border-radius: 12px;
        }

        h2 {
            color: #ff9800;
            margin-top: 0;
        }

        .top-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        a.button, button {
            display: inline-block;
            padding: 10px 15px;
            border: 0;
            border-radius: 6px;
            background: #ff9800;
            color: #171717;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        a.button:hover, button:hover {
            background: #ffad33;
        }

        .danger {
            background: #c62828;
            color: white;
        }

        .danger:hover {
            background: #e53935;
        }

        .success {
            padding: 12px;
            margin-bottom: 20px;
            background: #21452c;
            color: #a8e6b4;
            border-radius: 6px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #444;
            text-align: left;
            vertical-align: middle;
        }

        th {
            color: #ff9800;
            background: #1d1d1d;
        }

        tr:hover {
            background: #303030;
        }

        .status-active {
            color: #81c784;
            font-weight: bold;
        }

        .status-inactive {
            color: #ef9a9a;
            font-weight: bold;
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        .empty {
            color: #aaa;
            text-align: center;
            padding: 25px;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #ff9800;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Управління користувачами</h2>

    @if (session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif

    <div class="top-actions">

        <a href="{{ route('users.create') }}" class="button">
            + Додати користувача
        </a>

        <a href="{{ route('mayno') }}" class="button">
            До системи майна
        </a>

    </div>

    <div class="table-wrapper">

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Логін</th>
                    <th>Роль</th>
                    <th>Підрозділи</th>
                    <th>Статус</th>
                    <th>Дії</th>
                </tr>
            </thead>

            <tbody>

                @forelse ($users as $user)

                    <tr>
                        <td>{{ $user->Id }}</td>

                        <td>{{ $user->Login }}</td>

                        <td>
                            @switch($user->Role)
                                @case('admin')
                                    Адміністратор
                                    @break

                                @case('editor')
                                    Редагування
                                    @break

                                @default
                                    Перегляд
                            @endswitch
                        </td>

                        <td>
                            @if ($user->Role === 'admin')
                                Усі підрозділи
                            @else
                                {{ $user->departmentNames ?: 'Не призначено' }}
                            @endif
                        </td>

                        <td>
                            @if ($user->IsActive)
                                <span class="status-active">Активний</span>
                            @else
                                <span class="status-inactive">Неактивний</span>
                            @endif
                        </td>

                        <td>
                            <div class="actions">

                                <a
                                    href="{{ route('users.edit', $user->Id) }}"
                                    class="button"
                                >
                                    Редагувати
                                </a>

                                @if ((int)$user->Id !== (int)Auth::id())

                                    <form
                                        method="POST"
                                        action="{{ route('users.destroy', $user->Id) }}"
                                        onsubmit="return confirm('Ви дійсно бажаєте видалити користувача?');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="danger">
                                            Видалити
                                        </button>
                                    </form>

                                @endif

                            </div>
                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="6" class="empty">
                            Користувачів не знайдено.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <a href="{{ route('mayno') }}" class="back">
        ← Повернутися до системи
    </a>

</div>

</body>
</html>