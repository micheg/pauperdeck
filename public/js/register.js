const form =
    document.getElementById(
        'registerForm'
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

        const email =
            document.getElementById(
                'email'
            ).value.trim();

        const password =
            document.getElementById(
                'password'
            ).value;

        try {

            const data = await api(
                '/api/register',
                {
                    method: 'POST',

                    body: JSON.stringify({

                        username,
                        email,
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
                    || 'Register failed';

                return;
            }

            //
            // Redirect
            //

            window.location =
                '/login.html';

        } catch (err) {

            console.error(err);

            errorBox.innerHTML =
                'Network error';
        }
    }
);
