iTVPDownloader
===================

inspiracja: (wykopalisko DaZ)[http://www.wykop.pl/ramka/1319279/haksorowanie-tvp/]

wersja live:
[http://itvpdownloader.mmx3.pl/]


[![endorse](http://api.coderwall.com/emgiezet/endorsecount.png)](http://coderwall.com/emgiezet)

Bugi:
====
1. Nie działają linki po https (work in progress)


ToDo:
====

* support dla http://beta.vod.tvp.pl
* support do udostępniania pobranych linków na FB
* top10 pobranych linków.

Sugestie:
====
[ReV](http://www.wykop.pl/ludzie/rev/) - Co ta aplikacja ma do chmury? Nie jest ani dla niej specyficzna ani nie odnosi z niej żadnych korzyści. Cała logika tej aplikacji to 4 linijki kodu, a zaprzęgłeś do tego frameworki, które mają ich pewnie w sumie dobre kilkaset tysięcy. Klasyczny przypadek zabierania się z armatą na muchę. Jeszcze ta strona facebookowa, poważnie?!
Można było z tego zrobić jednolinijkowy bookmarklet. 

    javascript:(function()    {if(typeof(playVideo)===%22undefined%22)return;dojo.xhrGet({url:'/pub/stat/videofileinfo%3Fvideo_id='+playVideo.object_id,handleAs:'json',load:function(data){dojo.place('%3Cp%3EAdres%20pliku%20z%20filmem:%20%3Ca%20href=%22'%20+%20data.video_url%20+%20'%22%3E'+data.video_url+'%3C/a%3E%3C/p%3E','videoDebug','first');}});})();

