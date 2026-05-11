i#!/bin/bash

#
# MTG Pauper Deck Import Test
#
# Usage:
#
#   ./import-deck.sh
#

BASE_URL="http://pauperdeck.test"

USERNAME="michelangelo"
PASSWORD="646313"

COOKIE_FILE="cookies.txt"

echo ""
echo "==============================="
echo " LOGIN"
echo "==============================="
echo ""

wget \
    --save-cookies $COOKIE_FILE \
    --keep-session-cookies \
    --post-data='username='"$USERNAME"'&password='"$PASSWORD" \
    -O - \
    $BASE_URL/api/login

echo ""
echo ""
echo "==============================="
echo " CREATE DECK"
echo "==============================="
echo ""

CREATE_RESPONSE=$(wget \
    --load-cookies $COOKIE_FILE \
    --save-cookies $COOKIE_FILE \
    --keep-session-cookies \
    --header="Content-Type: application/json" \
    --post-data='{"name":"Mono-Blue Terror"}' \
    -O - \
    $BASE_URL/api/decks)

echo "$CREATE_RESPONSE"

echo ""
echo ""

#
# Extract deck_id
#

DECK_ID=$(echo "$CREATE_RESPONSE" \
    | grep -o '"deck_id":[0-9]*' \
    | grep -o '[0-9]*')

echo "Deck ID: $DECK_ID"

echo ""
echo "==============================="
echo " IMPORT DECK"
echo "==============================="
echo ""

DECK_TEXT=$(cat <<'EOF'
4 Tolarian Terror
4 Brainstorm
4 Counterspell
2 Sleep of the Dead
16 Island
4 Deem Inferior
4 Mental Note
4 Cryptic Serpent
4 Lorien Revealed
4 Thought Scour
2 Murmuring Mystic
3 Ponder
4 Delver of Secrets
1 Deep Analysis

Sideboard
4 Hydroblast
4 Annul
2 Gut Shot
4 Blue Elemental Blast
1 Envelop
EOF
)

JSON_PAYLOAD=$(printf '%s' "$DECK_TEXT" \
    | python3 -c 'import json,sys; print(json.dumps({"text": sys.stdin.read()}))')

wget \
    --load-cookies $COOKIE_FILE \
    --save-cookies $COOKIE_FILE \
    --keep-session-cookies \
    --header="Content-Type: application/json" \
    --post-data="$JSON_PAYLOAD" \
    -O - \
    $BASE_URL/api/decks/$DECK_ID/import

echo ""
echo ""
echo "==============================="
echo " DONE"
echo "==============================="
echo ""
