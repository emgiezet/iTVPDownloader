iTVPDownloader
===================

inspiracja: (wykopalisko DaZ)[http://www.wykop.pl/ramka/1319279/haksorowanie-tvp/]

wersja live:
[http://itvpdownloader.mmx3.pl/]


[![endorse](http://api.coderwall.com/emgiezet/endorsecount.png)](http://coderwall.com/emgiezet)

Instalacja:
===


    php composer.phar update
    chmod 777 logs -R

Bugi:
====
1. Nie działają linki po https (work in progress) - zgłoszone przez[blinxdxb](http://www.wykop.pl/ludzie/blinxdxb/) 


ToDo:
====

* support dla http://beta.vod.tvp.pl
  * test case:
    * strona filmu - http://beta.vod.tvp.pl/seriale/komediowe/rodzinkapl/wideo/odc-78/9248803
    * rozpoczęcie sesji: /pub/sess/initsession
    * view request: /pub/sess/viewrequest?object_id=$movieId
    * pobranie danych do ogl: http://www.tvp.pl/shared/cdn/tokenizer_v2.php?object_id=9248803&sdt_version=sdt-v2
    * link do pliku:  http://46.28.242.13/token/video/vod/9248803/20121217/1840089297/1b83136b-a3e8-40cd-82fa-149738826fdb
  * dane ze sniffingu requestów za pomocą charles-proxy w katalogu doc.


3. http://46.28.242.13/token/video/vod/9248803/20121217/1840089297/1b83136b-a3e8-40cd-82fa-149738826fdb
* support do udostępniania pobranych linków na FB
* top10 pobranych linków (z redisa).
* faq, polityka-prywatności.
* build na travis.ci i testy phpunit

Sugestie:
====
[ReV](http://www.wykop.pl/ludzie/rev/) - Co ta aplikacja ma do chmury? Nie jest ani dla niej specyficzna ani nie odnosi z niej żadnych korzyści. Cała logika tej aplikacji to 4 linijki kodu, a zaprzęgłeś do tego frameworki, które mają ich pewnie w sumie dobre kilkaset tysięcy. Klasyczny przypadek zabierania się z armatą na muchę. Jeszcze ta strona facebookowa, poważnie?!
Można było z tego zrobić jednolinijkowy bookmarklet. 

    javascript:(function()    {if(typeof(playVideo)===%22undefined%22)return;dojo.xhrGet({url:'/pub/stat/videofileinfo%3Fvideo_id='+playVideo.object_id,handleAs:'json',load:function(data){dojo.place('%3Cp%3EAdres%20pliku%20z%20filmem:%20%3Ca%20href=%22'%20+%20data.video_url%20+%20'%22%3E'+data.video_url+'%3C/a%3E%3C/p%3E','videoDebug','first');}});})();

