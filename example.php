<html>
<head>
<title>eksempel klient til stavekontrol</title>
</head>
<body>
<p>
Dette er en eksempel klient til DBCs  stavekontrol. Ved søgning på et ord hentes et antal forslag
til andre ord sorteret efter relevans.
</p>
<p>
Du kan bruge klienten som den er eller tilrette den til eget brug. Se link herunder til download af klienten.
</p>
<h3>Brug den som den er</h3>
<p>
Du kan bruge klienten ved fx. at indsætte en iframe på din hjemmeside. Så ser det sådan ud (prøv at lave en stavekontrol):

</p>
<iframe src="http://vision.dbc.dk/~pjo/webservices/spellcheck/client.php" width="50%">
</iframe> 
<p>
Der hvor du ønsker at sætte en bibliotek.dk stavekontrol ind på din hjemmeside, skal du indsætte følgende html:
</p>
<p>
<tt> 
&lt;iframe src="http://vision.dbc.dk/~pjo/webservices/spellcheck/client.php" width="50%"&gt;&lt;/iframe&gt;
</tt>

</p>
<h3>Brug den lokalt</h3>
<p>   Klienten er skrevet i php. Den bruger php SOAP extension, og er defineret som wsdl(Web Service Definition Language).
 For at kunne bruge den lokalt skal du:
</p>
<ul>
<li>have php 5 med SOAP extension installeret på din server</li>
   <li>Hente disse filer:</li>
   <ul>
   <li><a href="spellService.wsdl">spellService.wsdl</a></li>
   <li><a href="spellService.xsd">spellService.xsd</a></li>											      
   <li><a href="client.php">client.php</a></li>
   </ul>
</ul>
<p>
Filerne er pakket <a href="stavekontrol.tar">HER (stavekontrol.tar)</a>.
</p>
</body>
</html>
