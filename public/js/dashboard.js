async function loadDecks() {

    const data =
        await api('/api/decks');

    console.table(data);

    //
    // Unauthorized
    //

    if (data.error) {

        window.location =
            '/login';

        return;
    }

    //
    // Layout
    //

    $('#appLayout').w2layout({

        name: 'appLayout',

        panels: [

            {
                type: 'top',

                size: 50,

                resizable: false
            },

            {
                type: 'main'
            }
        ]
    });

    //
    // Toolbar container
    //

    w2ui.appLayout.html(
        'top',
        '<div id="topToolbar" style="width:100%;height:100%;"></div>'
    );

    //
    // Grid container
    //

    w2ui.appLayout.html(
        'main',
        '<div id="decksGrid" style="width:100%;height:100%;"></div>'
    );

    //
    // Toolbar
    //

    $('#topToolbar').w2toolbar({

        name: 'topToolbar',

        items: [

            {
                type: 'html',

                id: 'logo',

                html:
                    `
                    <div style="
                        padding: 12px;
                        font-size: 18px;
                        font-weight: bold;
                    ">
                        PauperDeck
                    </div>
                    `
            },

            {
                type: 'spacer'
            },

            {
                type: 'button',

                id: 'logout',

                text: 'Logout'
            }
        ],

        onClick: async function (
            event
        ) {

            if (
                event.target === 'logout'
            ) {

                await api(
                    '/api/logout',
                    {
                        method: 'POST'
                    }
                );

                window.location =
                    '/login';
            }
        }
    });

    //
    // Grid
    //

    $('#decksGrid').w2grid({

        name: 'decksGrid',

        show: {

            toolbar: true,

            footer: true
        },

        onDblClick(event) {
            window.location =
                '/deck/'
                + event.detail.recid;
        },

        columns: [

            {
                field: 'name',

                text: 'Deck Name',

                size: '50%'
            },

            {
                field: 'format_name',

                text: 'Format',

                size: '120px'
            },

            {
                field: 'updated_at',

                text: 'Updated',

                size: '180px'
            }
        ],

        records: data.map(
            (deck) => ({

                recid: deck.id,

                ...deck
            })
        )
    });
}



loadDecks();
