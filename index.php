
<!DOCTYPE html>
<html lang="en">
<head>

    <!-- Include BootStrap and Jquery-->

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-theme.min.css"
    <link rel="stylesheet" href="css/index.css">

</head>
<body>

<div class="container" align="center" id="main">
    <div id="logo">
        <h1>WEEEEEE</h1>
    </div>
    <form class="form-inline" action="results.php" method="post" id="form">
        <div class="form-group">

            <input name="query"type="text" placeholder="Enter Search query" class="form-control"/>
        </div>

        <div class="form-group">
            <input type="submit" name="submit" value="Search" class="btn btn-danger"/>
        </div>
    </form>

</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>