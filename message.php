<?php
    if (!isset($_REQUEST["r"]))
        exit(1);

    require "settings.php";
    require "weblib.php";
    
    $id = $_REQUEST['id'];
    $thread = $_REQUEST['thread'];
    $message = $_REQUEST['message'];

?>
<div id="board:<?php echo $id ?>:thread:<?php echo $thread ?>:message:<?php echo $message ?>">
<?php
        echo showMessage($id,$thread,$message);
?>
</div>