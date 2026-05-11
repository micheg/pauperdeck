<?php require __DIR__ . '/layout/header.php';
?>

<script>

const DECK_ID =
<?php echo json_encode( $deckId );
?>;

</script>

<div
id = 'deckLayout'
style = "
        width:100%;
        height:100vh;
    "
></div>

<?php require __DIR__ . '/layout/footer.php';
?>

<script src = '/js/deck.js'></script>