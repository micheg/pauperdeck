<?php require __DIR__ . '/layout/header.php'; ?>

<div class="auth-box">

    <h1>PauperDeck</h1>

    <form id="loginForm">

        <div class="field">

            <label>Username</label>

            <input
                type="text"
                id="username"
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

            Login

        </button>

    </form>

    <div
        class="error"
        id="errorBox"
    ></div>

    <div class="auth-link">

        <a href="/register">

            Create account

        </a>

    </div>

</div>

<script src="/js/login.js"></script>

<?php require __DIR__ . '/layout/footer.php'; ?>
