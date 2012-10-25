{include file="header.tpl"}

<div style="text-align:justify;"><a href="images/dropbox-icon.pdf"><img src="images/dropbox-icon.png" align="left" border="0" alt="[dropbox]"/></a>
<h4>À propos du service {#ServiceTitle#}...</h4>

Les e-mails avec des pièces jointes volumineuses peuvent causer des ravages sur les serveurs de messagerie et
ordinateurs des utilisateurs finaux. Le téléchargement de tels courriels peut prendre plusieurs heures sur
une connexion Internet lente et bloquer tout envoi ou réception de messages
pendant ce temps. Dans certains cas, le téléchargement échoue à plusieurs reprises,
ce qui empêche la réception de tout courriel pour le destinataire. De plus
les clients de messagerie Internet ajoutent considérablement à la taille du fichier envoyé. pour
Par exemple, l'enregistrement d'un message Outlook avec une pièce jointe ajoute
jusqu'à 40% à la taille du fichier. Pour partager des fichiers de plus de 1 Mo, utilisez le
{#ServiceTitle#} pour rendre temporairement disponible un fichier (ou des fichiers)
à un autre utilisateur à travers l'Internet, de manière sécurisée et efficace.<br/>
<br/>
Il y a deux types d'utilisateurs qui peuvent accéder au système
{#ServiceTitle#} :  les utilisateurs<i>internes</i>, qui sont associés
au {#OrganizationType#}, et les utilisateurs <i>externes</i>,
qui peuvent être n'importe qui sur Internet.<br/>
<br/>
Un utilisateur <i>interne</i> a la permission de partager un fichier
à n'importe quel autre utilisateur, peu importe si ce-dernier est un utilisateur <i>interne</i>
ou <i>externe</i>.  Un utilisateur <i>externe</i> peut seulement utiliser {#ServiceTitle#}
pour partager un fichier avec un utilisateur <i>interne</i>.


Il y a deux façons de partager plusieurs fichiers à la fois sur {#ServiceTitle#}:

<ul>
  <li>Attacher chaque fichier individuellement sur la page de partage</li>
  <li>Compresser les fichiers à partager dans une archive (p. ex. ZIP, RAR ou 7Z) 
  et attacher le fichier résultant sur la page de partage.  </li>
</ul>

<b>Pour partager un fichier</b><br/>
<blockquote style="text-align:justify;border-bottom:2px dotted #C0C0C0;">
Lorsqu'un utilisateur partage un fichier, il fournit certaines informations
qui leur identifie (nom, organisation, email et
adresse); information permettant d'identifier le destinataire (nom et 
adresse email), et choisissent quels fichiers doivent être partagés.
Si les fichiers sont téléchargés sur le serveur avec succès, un courriel est envoyé au destinataire
expliquant qu'un fichier a été partagé avec eux. Ce courriel contient également un lien
pour accéder au fichier en question.
D'autres informations (l'adresse IP et / ou le 
nom de l'ordinateur à partir duquel le fichier a été partagé, par exemple) sont conservées,
afin d'aider le destinataire à vérifier l'identité de l'expéditeur.<br/>
<br/>
</blockquote>

<b>Pour récupérer un fichier qui vous a été partagé</b><br/>
<blockquote style="text-align:justify;border-bottom:2px dotted #C0C0C0;">
Il y a deux façons de récupérer un fichier qui a été partagé avec vous.
<ul>
  <li>Tous les utilisateurs (internes ou externes) peuvent cliquer le lien dans le courriel de notification.</li>
  <li>Un utilisateur interne, une fois authentifié sur {#ServiceTitle#}, peut affichier leur "Inbox" qui est une liste de tous les fichiers qui leur ont été partagés.</li>
</ul>
Lorsqu'il accède à un partage, l'utilisateur verra les informations suivantes:
<ul>
  <li>La liste des fichiers partagés</li>
  <li>Les informations sur l'origine et le destinataire du partage</li>
  <li>Le nom de l'ordinateur ou l'adresse IP à partir d'où a été partagé le fichier</li>
  <li>(Facultatif) Le nombre de fois que le partage a été téléchargé</li>
</ul>
Le destinataire a {$keepForDays} jours pour télécharger les fichiers partagés.  Chaque soir, les fichiers plus vieux que {$keepForDays} jours sont supprimés du système.<br/>
<br/>
</blockquote>

Veuillez noter que les fichiers envoyés sont scannés pour détecter les virus, mais le
destinataire devrait toujours faire preuve de précaution lors du téléchargement et
seulement ouvrir les fichiers auxquels il fait confiance. Cela peut être aussi simple que de vérifier avec
l'expéditeur mentionné dans le courriel de notification qu'il ou elle a en effet
partagé un fichier.<br/>
<br/>

</div>

<hr/>

<h4>Reprise du téléchargement des fichiers</h4>

La plupart des navigateurs Web modernes supportent la reprise de téléchargement.
Ceci veut dire que si le téléchargement est interrompu, le navigateur a la possibilité
de demander le téléchargement seulement de la portion non-téléchargée plutôt que de recommencer
le téléchargement à zéro.

<br/>

<hr/>

<h4>Limitation sur la taille des fichiers téléchargés (partagés)</h4>

La possibilité de téléchargement de fichiers très volumineux dépend du navigateur que vous utilisez.
Les navigateurs suivants ont été testés.<br>
<br>

<center>
<table border="1" cellpadding="4" cellspacing="1">
  <tr style="background-color:#2F2F4F;color:white;"><th>&nbsp;</th><th>Navigateur</th><th>Téléchargements de fichiers volumineux</th></tr>
  <tr>
    <td rowspan="4" style="background-color:#2F2F4F;color:white;text-align:center;">M<br/>A<br/>C</td>
    <td><a href="http://www.apple.com/safari">Safari</a> 5.x</td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">Oui</td>
  </tr>
  <tr>
    <td><a href="http://www.google.com/chrome">Chrome</a></td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">Oui</td>
  </tr>
  <tr>
    <td><a href="http://www.mozilla.com/en-US/firefox/">Firefox</a> 5.x</td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">Oui</td>
  </tr>
  <tr>
    <td><a href="http://www.opera.com/">Opera</a> 10.x</td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">Oui</td>
  </tr>
  <tr>
    <td rowspan="5" style="background-color:#2F2F4F;color:white;text-align:center;">P<br/>C</td>
    <td><a href="http://www.microsoft.com/ie">Internet Explorer</a> 6</td>
    <td style="text-align:center;font-weight:bold;color:#A00000;">Non</td>
  </tr>
  <tr>
    <td><a href="http://www.microsoft.com/ie">Internet Explorer</a> 7</td>
    <td style="text-align:center;font-weight:bold;color:#A00000;">Non</td>
  </tr>
  <tr>
    <td><a href="http://www.microsoft.com/ie">Internet Explorer</a> 9 64-bit</td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">Oui</td>
  </tr>
</table>
</center>
<br/>

Le service {#ServiceTitle#} lui-même impose des limites sur la taille des fichiers partagés.
 Même pour les navigateurs supportant le transfert de fichiers volumineux, la taille de chaque fichier
 ne doit pas dépasser {$maxFileSize}, ou {$maxDropoffSize} au total pour chaque session de partage.<br/>
<br/>

<br/>Si vous éprouvez des difficultés lors du téléchargement de fichier volumineux, par exemple si
<ul>
  <li>Votre navigateur vous annonce que la connection a été rompue</li>
  <li>Une page d'erreur indique qu'aucun fichier n'a pu être téléchargé</li>
</ul>
votre connexion Internet n'est pas assez rapide pour effectuer le téléchargement à l'intérieur de la 
limite de 2 heures allouée au téléchargement.
<hr/>

<p style="font-size:10px;" align="left"><a href="http://www.php.net/"><img src="images/PHP5.png" align="right" border="0" alt="[php5]"/></a>
Based upon the original Perl UD Dropbox software written by
Doke Scott.  Version {$ztVersion} has been developed by <a
href="mailto:Jules@Zend.To">Julian Field</a>.
</p>

{include file="footer.tpl"}
