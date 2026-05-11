async function api(
    url,
    options = {}
) {

    const response =
        await fetch(
            url,
            {

                credentials: 'include',

                headers: {
                    'Content-Type':
                        'application/json'
                },

                ...options
            }
        );

    //
    // Parse JSON
    //

    const data =
        await response.json();

    return data;
}
