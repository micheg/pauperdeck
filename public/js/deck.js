let currentDeck = null;

let currentCards = [];

/*
|--------------------------------------------------------------------------
| LOAD
|--------------------------------------------------------------------------
*/

async function loadDeck() {
  //
  // Deck
  //

  currentDeck = await api("/api/decks/" + DECK_ID);

  //
  // Cards
  //

  currentCards = await api("/api/decks/" + DECK_ID + "/cards");

  //
  // Layout
  //

  $("#deckLayout").w2layout({
    name: "deckLayout",

    panels: [
      {
        type: "top",

        size: 50,
      },

      {
        type: "left",

        size: 400,

        resizable: true,
      },

      {
        type: "main",
      },

      {
        type: "right",

        size: 320,

        resizable: true,
      },
    ],
  });

  //
  // Containers
  //

  w2ui.deckLayout.html(
    "top",
    '<div id="deckToolbar" style="width:100%;height:100%;"></div>',
  );

  w2ui.deckLayout.html(
    "left",
    '<div id="searchGrid" style="width:100%;height:100%;"></div>',
  );

  w2ui.deckLayout.html(
    "main",
    '<div id="deckGrid" style="width:100%;height:100%;"></div>',
  );

  w2ui.deckLayout.html(
    "right",
    '<div id="previewPanel" style="width:100%;height:100%;overflow:auto;"></div>',
  );

  //
  // Render
  //

  renderToolbar();

  renderSearchGrid2();

  renderDeckGrid();

  renderPreview(null);
}

/*
|--------------------------------------------------------------------------
| TOOLBAR
|--------------------------------------------------------------------------
*/

function renderToolbar() {
  $("#deckToolbar").w2toolbar({
    name: "deckToolbar",

    items: [
      {
        type: "button",

        id: "back",

        text: "Back",
      },

      {
        type: "html",

        id: "title",

        html: `
                    <div style="
                        padding:10px;
                        font-size:18px;
                        font-weight:bold;
                    ">
                        ${currentDeck.name}
                    </div>
                    `,
      },
    ],

    onClick(event) {
      if (event.target === "back") {
        window.location = "/dashboard";
      }
    },
  });
}

/*
|--------------------------------------------------------------------------
| SEARCH GRID
|--------------------------------------------------------------------------
*/
function renderSearchGrid2() {
  $("#searchGrid").w2grid({
    name: "searchGrid",

    show: {
      toolbar: true,
      footer: true,

      toolbarSearch: false,
      toolbarReload: false,
      toolbarColumns: false,
    },

    columns: [
      {
        field: "name_en",
        text: "Name",
        size: "60%",
      },

      {
        field: "mana_cost",
        text: "Mana",
        size: "80px",
      },

      {
        field: "main_type",
        text: "Type",
        size: "120px",
      },
    ],
    onClick(event) {
      const card = this.get(event.detail.recid);

      if (!card) {
        return;
      }

      renderPreview(card.img_a);
    },
    toolbar: {
      items: [
        {
          type: "html",

          id: "search",

          html: `
            <div>
              <input
                id="cardSearchInput"
                type="text"
                placeholder="Search cards..."
                style="
                  width:220px;
                  padding:6px;
                "
              >
            </div>
          `,
        },
      ],
    },

    records: [],
  });

  //
  // Wait toolbar render
  //

  setTimeout(() => {
    const input = document.getElementById("cardSearchInput");

    if (!input) {
      return;
    }

    input.addEventListener("input", async (e) => {
      const q = e.target.value.trim();

      if (q.length < 2) {
        w2ui.searchGrid.records = [];

        w2ui.searchGrid.refresh();

        return;
      }

      console.log("Searching:", q);

      const results = await api("/api/cards?q=" + encodeURIComponent(q));

      console.table(results);

      w2ui.searchGrid.records = results.map((card, index) => ({
        recid: index + 1,

        ...card,
      }));

      w2ui.searchGrid.refresh();
    });
  }, 100);

  //
  // Double click add
  //

  w2ui.searchGrid.on("dblClick", function (event) {
    const card = this.get(event.recid);

    if (!card) {
      return;
    }

    alert("TODO add card: " + card.name_en);
  });
}

/*
|--------------------------------------------------------------------------
| DECK GRID
|--------------------------------------------------------------------------
*/

function renderDeckGrid() {
  $("#deckGrid").w2grid({
    name: "deckGrid",

    show: {
      toolbar: false,

      footer: true,
    },

    columns: [
      {
        field: "quantity",

        text: "Qty",

        size: "60px",
      },

      {
        field: "name_en",

        text: "Name",

        size: "50%",
      },

      {
        field: "mana_cost",

        text: "Mana",

        size: "100px",
      },

      {
        field: "main_type",

        text: "Type",

        size: "160px",
      },

      {
        field: "is_sideboard",

        text: "Side",

        size: "80px",

        render(record) {
          return record.is_sideboard ? "Yes" : "Main";
        },
      },
      {
        field: "actions",

        text: "",

        size: "120px",

        render(record) {
          return `
    <button
        class="qty-btn add-btn"
        onclick="event.stopPropagation()"
        data-id="${record.id}"
    >
        +
    </button>

    <button
        class="qty-btn sub-btn"
        onclick="event.stopPropagation()"
        data-id="${record.id}"
    >
        -
    </button>

    <button
        class="qty-btn remove-btn"
        onclick="event.stopPropagation()"
        data-id="${record.id}"
    >
        ×
    </button>
        `;
        },
      },
    ],

    records: currentCards.map((card, index) => ({
      recid: index + 1,

      ...card,
    })),

    onClick(event) {
      const card = this.get(event.detail.recid);
      if (!card) {
        return;
      }

      renderPreview(card.img_a);
    },

    onDblClick(event) {
      const card = this.get(event.recid);

      if (!card) {
        return;
      }

      alert("TODO remove card: " + card.name_en);
    },
  });
}

/*
|--------------------------------------------------------------------------
| PREVIEW
|--------------------------------------------------------------------------
*/

function renderPreview(image) {
  if (!image) {
    $("#previewPanel").html(
      `
              <div class="toolbar-search">
                No card selected
            </div>
            `,
    );

    return;
  }

  $("#previewPanel").html(
    `
        <div style="padding:20px;">

            <img
                src="${image}"
                style="
                    width:100%;
                    border-radius:12px;
                "
            >

        </div>
        `,
  );
}

loadDeck();
