
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Авторизація</title>

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
            background: #181818;
            color: #eeeeee;
            font-family: Arial, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 35px;
            background: #252525;
            border: 1px solid #444;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, .5);
        }

        h2 {
            margin: 0 0 30px;
            text-align: center;
            color: #ff9800;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            background: #171717;
            border: 1px solid #555;
            border-radius: 6px;
            color: white;
            font-size: 16px;
        }

        input:focus {
            outline: none;
            border-color: #ff9800;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 13px;
            background: #ff9800;
            border: none;
            border-radius: 6px;
            color: #171717;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #ffad33;
        }

        .error {
            margin-bottom: 18px;
            padding: 12px;
            background: #542727;
            border: 1px solid #a94442;
            border-radius: 6px;
            color: #ffbaba;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="login-container">

    <h2>Вхід до системи</h2>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf

        <label for="login">Логін</label>

        <input
            type="text"
            id="login"
            name="login"
            value="{{ old('login') }}"
            maxlength="100"
            autocomplete="username"
            required
            autofocus
        >

        <label for="password">Пароль</label>

        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        >

        <label class="remember">
            <input type="checkbox" name="remember" value="1">
            Запам'ятати мене
        </label>

        <button type="submit">Увійти</button>
    </form>

</div>

</body>
</html>
