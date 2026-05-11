<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>PauperDeck - Register</title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <link
        rel="stylesheet"
        href="https://unpkg.com/w2ui@2.0/dist/w2ui.min.css"
    >

    <link
        rel="stylesheet"
        href="/css/app.css"
    >

</head>

<body>

    <div class="auth-box">

        <h1>Create Account</h1>

        <form id="registerForm">

            <div class="field">

                <label>Username</label>

                <input
                    type="text"
                    id="username"
                    required
                >

            </div>

            <div class="field">

                <label>Email</label>

                <input
                    type="email"
                    id="email"
                    required
                >

            </div>

            <div class="field">

                <label>Password</label>

                <input
                    type="password"
                    id="password"
                    required
                >

            </div>

            <button type="submit">

                Register

            </button>

        </form>

        <div
            class="error"
            id="errorBox"
        ></div>

        <div class="auth-link">

            <a href="/login">

                Back to login

            </a>

        </div>

    </div>

    <script src="https://unpkg.com/w2ui@2.0/dist/w2ui.min.js"></script>

    <script src="/js/api.js"></script>

    <script src="/js/register.js"></script>

</body>
</html>
