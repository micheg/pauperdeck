const form =
    document.getElementById(
        'loginForm'
    );

const errorBox =
    document.getElementById(
        'errorBox'
    );



form.addEventListener(
    'submit',
    async (e) => {

        e.preventDefault();

        errorBox.innerHTML = '';

        const username =
            document.getElementById(
                'username'
            ).value.trim();

        const password =
            document.getElementById(
                'password'
            ).value;

        try {

            const data = await api(
                '/api/login',
                {
                    method: 'POST',

                    body: JSON.stringify({

                        username,
                        password
                    })
                }
            );

            //
            // Error
            //

            if (!data.success) {

                errorBox.innerHTML =
                    data.error
                    || 'Login failed';

                return;
            }

            //
            // Redirect
            //

            window.location =
                '/dashboard';

        } catch (err) {

            console.error(err);

            errorBox.innerHTML =
                'Network error';
        }
    }
);
