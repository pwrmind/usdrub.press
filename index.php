<?php
session_start();
?>
<html lang="ru">
    <head>
        <link rel="image/png" href=​"assets/images/favicon.png" type="image/x-icon">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width">
        <meta name="description" content="На этой странице вы сможете найти последние новости валютной пары Доллар США Российский рубль со всегомира.">
        <meta name="keywords" content="USD RUB новости,Доллар США Российский рубль новости,новости Доллар США,Российский рубль новости,Доллар США к Российский рубль новости, курс,график,сегодня,динамика,изменений,курс,доллар сша,рубль,месяц,колебания">
        <title>UsdRub.press 💰 Новости про Доллар США и Российский рубль (USD vs RUB)</title>
        <link rel="stylesheet" type="text/css" href="/assets/styles/main.css">
    </head>
    <body itemscope="" itemtype="http://schema.org/WebPage">
        <header class="home">
            USDRUB<span id="icon">💰</span><span style="color: black;">PRESS</span>
        </header>
        <?php

        function render( $title, $link, $author = "", $pubdate = "" ) {
            echo '<div class="news" itemprop="itemListElement" itemscope itemtype="http://schema.org/NewsArticle">';
            echo '<meta itemprop="name" content="' . $title . '" />';
            echo '<meta itemprop="position" content="1" />';
            echo '<meta itemprop="image" content="{{ news.image | escape }}" />';
            echo '<meta itemprop="datePublished" content="' . $pubdate . '">';
            echo '<h4 itemprop="url" href="' . $url . '">';
            echo '<span itemprop="headline">' . $title . '</span>';
            echo '</h4>';
            echo '<div itemprop="articleSection"></div>';
            echo '<div itemscope itemprop="author" itemtype="http://schema.org/Organization">';
            echo '<a itemprop="url" href="' . $link . '"><span itemprop="name">' . $author . '</span></a>';
            echo '</div>';
            // echo '<div itemscope itemprop="publisher" itemtype="http://schema.org/Organization">';
            // echo '<a itemprop="url" href="http://www.example.com/GloboCorp"><span itemprop="name">GloboCorp</span></a>';
            // echo '<a itemprop="logo" href="http://www.example.com/GloboCorp"><span itemprop="name">GloboCorp</span></a>';
            // echo '</div>';
            echo '</div>';
        }

        function get_headers_from_curl_response($response) {
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

        function fetch( $channel ) {
            $url = $channel->url;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'If-Modified-Since: ' . $channel->lastModified,
                'If-None-Match: ' . $channel->etag
            ));
            curl_setopt($ch, CURLINFO_RESPONSE_CODE, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            $data = curl_exec( $ch );
            $responseCode = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
            // print_r( $responseCode );
            if ( $responseCode === 304 ) {
                // return;
            }
            $headerSize = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
            $headerText = substr( $data, 0, $headerSize );
            $headers = get_headers_from_curl_response( $headerText );
            $body = substr( $data, $headerSize );

            curl_close( $ch );
            
            $xmlDoc = new DOMDocument();
            $xmlDoc->loadXML( $body );
            
            $xmlChannel = $xmlDoc->getElementsByTagName('channel')->item(0);
            $channel->title = $xmlChannel->getElementsByTagName('title')->item(0)->childNodes->item(0)->nodeValue;
            $channel->link = $xmlChannel->getElementsByTagName('link')->item(0)->childNodes->item(0)->nodeValue;
            //$channel->description = ($xmlChannel->getElementsByTagName('description') !== null) ? $xmlChannel->getElementsByTagName('description')->item(0)->childNodes->item(0)->nodeValue : "";
            $channel->etag = (isset($headers["ETag"])) ? $headers["ETag"] : "";
            $channel->lastModified = (isset($headers["Last-Modified"])) ? $headers["Last-Modified"] : "";
            
            $items = $xmlDoc->getElementsByTagName('item');

            for ($i=0; $i<$items->length; $i++) {
                $title = $items->item($i)->getElementsByTagName('title')->item(0)->childNodes->item(0)->nodeValue;
                $link = $items->item($i)->getElementsByTagName('link')->item(0)->childNodes->item(0)->nodeValue;
                //$author = $items->item($i)->getElementsByTagName('author')->item(0)->childNodes->item(0)->nodeValue;
                //$pubdate = $items->item($i)->getElementsByTagName('pubdate')->item(0)->childNodes->item(0)->nodeValue;
                //$item_desc = $items->item($i)->getElementsByTagName('description')->item(0)->childNodes->item(0)->nodeValue;
                if(stripos( $title, "usd", 0) || stripos( $title, "доллар", 0)) {
                    //echo ("<p><a href='" . $item_link . "'>" . $item_title . "</a>");
                    //echo ("<br>");
                    // render( $title, $link, $author, $pubdate );
                    render( $title, $link, $channel->link . ' - ' . $channel->title );
                    //echo ($item_desc . "</p>");
                }
            }
        }

        class Channel
        {
            public $url = "";
            public $title = "";
            public $link = "";
            public $description = "";
            public $etag = "";
            public $lastModified = "";
        }

        $rssChannels = array(
            "https://ru.investing.com/rss/news_1.rss",
            "http://static.feed.rbc.ru/rbc/logical/footer/news.rss",
            "https://russian.rt.com/rss",
            "https://www.investing.com/rss/forex.rss"
        );

        if (!isset($_SESSION["channels"])) {
            $_SESSION["channels"] = array();
        }

        //print_r( $_SESSION );

        foreach ($rssChannels as $key => $value) {
            echo "<div>";
            if (isset($_SESSION["channels"][$value])) {
                //echo $value . " already added to session";
            } else {
                $_SESSION["channels"][$value] = new Channel();
                $_SESSION["channels"][$value]->url = $value;
                //echo $value . " added to session";
            }
            echo "</div>";
        }

        foreach ($_SESSION["channels"] as $key => $channel) {
            fetch( $channel );
        }

        ?>
        <script type="text/javascript" src="assets/scripts/main.js"></script>
    </body>
</html>