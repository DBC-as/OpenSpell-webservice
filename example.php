<html>
<head>
<title>eksempel klient til stavekontrol</title>
</head>
<body>
<h2> Eksempel på brug af OpenSpell-webservice</h2>
<p>
Dette er en eksempel klient til DBCs stavekontrol. Ved søgning på et ord hentes et antal forslag
til andre ord sorteret efter relevans.
</p>
<p>
Webservicen er beskrevet som wsdl. Du kan se definitionen (WSDL)<a href="openspell.wsdl"> HER</a>
<br/>
Du kan se skemadfinitionen (XSD)<a href="openspell.xsd"> HER</a>

</p>

<p>
Du kan bruge klienten som den er eller tilrette den til eget brug. Se link herunder til download af klienten.
</p>
<h3>Brug den som den er - eksempel med SOAP</h3>
<p>
Du kan bruge klienten ved fx. at indsætte en iframe på din hjemmeside. Så ser det sådan ud (prøv at lave en stavekontrol):

</p>
<iframe src="http://didicas.dbc.dk/openspell/client.php" width="50%">
</iframe> 
<p>
Der hvor du ønsker at sætte en bibliotek.dk stavekontrol ind på din hjemmeside, kan du indsætte følgende html:
</p>
<p>
<tt> 
&lt;iframe src="http://didicas.dbc.dk/openspell/client.php" width="50%"&gt;&lt;/iframe&gt;
</tt>
</p>
<h3>Eksempler på REST</h3>
<p>
				Webservicen kan levere svar i XML, SOAP eller JSON her er nogle eksempler
</p>

</body>
</html>
