<?php
//include our settings, connect to database etc.
//include dirname($_SERVER['DOCUMENT_ROOT']).'/cfg/settings.php';
//getting required data
//$DATA=dbgetarr("SELECT * FROM links");

class Site {
    public $link = "";
    public $title = "";
    public $description = "";
    public $keywords = "";
}

class Page {
    public $link = "";
    public $title = "";
    public $description = "";
    public $keywords = "";
}

class NewsItem {
    public $title = "";
    public $link = "";
    public $author = "";
    public $pubdate = "";
}

function getHeadersFromCurlResponse($response) {
    $headers = array();

    $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

    foreach (explode("\r\n", $header_text) as $i => $line)
        if ($i === 0)
            $headers['http_code'] = $line;
        else
        {
            list ($key, $value) = explode(': ', $line);

            $headers[$key] = $value;
        }

    return $headers;
}

function loadNewsFromSource( $channel = null ) {
    $newsList = array();

    $url = $channel;
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
    curl_setopt( $ch, CURLOPT_VERBOSE, TRUE );
    curl_setopt( $ch, CURLOPT_HEADER, TRUE );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
    // curl_setopt( $ch, CURLOPT_HTTPHEADER, array (
    //     'If-Modified-Since: ' . $channel->lastModified,
    //     'If-None-Match: ' . $channel->etag
    // ));
    curl_setopt($ch, CURLINFO_RESPONSE_CODE, TRUE);

    $data = curl_exec( $ch );
    $responseCode = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
    // print_r( $responseCode );
    if ( $responseCode === 304 ) {
        // return;
    }
    $headerSize = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
    $headerText = substr( $data, 0, $headerSize );
    $headers = getHeadersFromCurlResponse( $headerText );
    $body = substr( $data, $headerSize );

    curl_close( $ch );
    
    $xmlDoc = new DOMDocument();
    $xmlDoc->loadXML( $body );
    
    $xmlChannel = $xmlDoc->getElementsByTagName('channel')->item(0);
    // $channel->title = $xmlChannel->getElementsByTagName('title')->item(0)->childNodes->item(0)->nodeValue;
    // $channel->link = $xmlChannel->getElementsByTagName('link')->item(0)->childNodes->item(0)->nodeValue;
    // $channel->description = ($xmlChannel->getElementsByTagName('description') !== null) ? $xmlChannel->getElementsByTagName('description')->item(0)->childNodes->item(0)->nodeValue : "";
    // $channel->etag = (isset($headers["ETag"])) ? $headers["ETag"] : "";
    // $channel->lastModified = (isset($headers["Last-Modified"])) ? $headers["Last-Modified"] : "";
    
    $items = $xmlDoc->getElementsByTagName('item');

    for ($i=0; $i<$items->length; $i++) {
        $title = $items->item($i)->getElementsByTagName('title')->item(0)->childNodes->item(0)->nodeValue;
        $link = $items->item($i)->getElementsByTagName('link')->item(0)->childNodes->item(0)->nodeValue;
        // $author = $items->item($i)->getElementsByTagName('author')->item(0)->childNodes->item(0)->nodeValue;
        // $pubdate = $items->item($i)->getElementsByTagName('pubdate')->item(0)->childNodes->item(0)->nodeValue;
        // $item_desc = $items->item($i)->getElementsByTagName('description')->item(0)->childNodes->item(0)->nodeValue;
        if(stripos( $title, "usd", 0) || stripos( $title, "доллар", 0)) {
            $newsItem = new NewsItem();
            $newsItem->title = $title;
            $newsItem->link = $link;
            $newsItem->author = "";
            $newsItem->pubdate = "";

            array_push( $newsList, $newsItem );
        }
    }

    return $newsList;
}

function loadNewsFromSources( $channels = null ) {
    $newsList = array();

    foreach ($channels as $key => $channel) {
        $newsList = array_merge( $newsList, loadNewsFromSource( $channel ));
    }

    return $newsList;
}

$site = new Site();
$site->link = "http://usdrub.press";
$site->title = "USDRUB.PRESS";
$site->description = "На этой странице вы сможете найти последние новости валютной пары Доллар США Российский рубль со всегомира.";
$site->keywords = "USD RUB новости,Доллар США Российский рубль новости,новости Доллар США,Российский рубль новости,Доллар США к Российский рубль новости, курс,график,сегодня,динамика,изменений,курс,доллар сша,рубль,месяц,колебания";

$page = new Page();
$page->title = " 💰 Новости про Доллар США и Российский рубль (USD vs RUB)";

$channels = array(
    "https://ru.investing.com/rss/news_1.rss",
    "http://static.feed.rbc.ru/rbc/logical/footer/news.rss",
    "https://russian.rt.com/rss",
    "https://www.investing.com/rss/forex.rss"
);

$DATA = loadNewsFromSources( $channels );

$master = "./templates/master.php";
$head = "./templates/head.php";
$scripts = "./templates/scripts.php";
$header = "./templates/header.php";
$footer = "./templates/footer.php";
$newsList = "./templates/newsList.php";
$newsItem = "./templates/newsItem.php";

include $master;

?>