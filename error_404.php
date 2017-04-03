<? 		
$domain = $_SERVER['HTTP_HOST']; $path = $_SERVER['REQUEST_URI']; $err = "http://" . $domain . $path;			
$url = 'http://seagrant.mit.edu/error_404.php?err='.$err;
header("Location: $url");
?>