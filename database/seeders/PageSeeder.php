<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->runFAQ();
    }

    /**
     * Run the "FAQ" page seeds.
     *
     * @return void
     */
    public function runFAQ()
    {
        // check if page with name "FAQ" exists
        $page = Page::where('slug', 'faq')->first();
        if ($page) {
            return;
        }

        // create a new page
        $page = new Page();
        $page->title = 'FAQ';
        $page->slug = 'faq';
        $page->sort_order = 0;
        $page->content = '
        <h2>📅 Stundenplan erstellen</h2>
        <p>Hier findet ihr einmal eine Anleitung, wie man den Stundenplan zusammenstellt, falls ihr noch mal nachschauen wollt oder an der Erstiwoche nicht teilnehmen k&ouml;nnt. <br /><strong><a href="https://www.campusoffice.fh-aachen.de/">Campus Office</a> - </strong>hiermit erstellt ihr euren Stundenplan.</p>
        <p><video controls="controls" width="900" height="450">
        <source src="https://fh-aachen.sciebo.de/s/dz2ahLJn1YcijZE/download" /></video></p>
        <hr />
        <h2>🗺️ Lageplan</h2>
        <p>Einmal der Lageplan des Campus. Im D-Geb&auml;ude (markiert) startet am Montag die Erstiwoche.</p>
        <p><img src="https://akwi2019.fh-aachen.de/wp-content/uploads/Campusplan_PNG-1-1280x749.png" alt="" width="900" height="527" /></p>
        <hr />
        <h2>🌐 W-LAN Anleitung</h2>
        <p>Eduroam ist eine internationale Initiative der Hochschulen, die es uns Studierenden erm&ouml;glicht, weltweit an allen teilnehmenden Hochschulen einfach und kostenlos WLAN zu nutzen. Hei&szlig;t auch an Geb&auml;uden der RWTH oder an anderen Hochschulen weltweit.<br />Die Anleitung, wie ihr Eduroam einrichtet, findet ihr <a href="https://www.fh-aachen.de/hochschule/datenverarbeitungszentrale/netzanbindung/wlan">hier</a>.</p>
        <hr />
        <h2>🛎️ Services der FH</h2>
        <p>Die FH bietet euch viele unterschiedliche Anlaufstellen und Services. Wir haben euch einige aufgelistet.</p>
        <ul style="list-style-type: circle;">
        <li><a href="https://h1.fh-aachen.de/qisserver/pages/cs/sys/portal/hisinoneStartPage.faces">h1.fh-aachen.de</a> - Hier findet ihr eine &Uuml;bersicht zu euren Bescheinigungen wie bspw.: Studiengangsbescheinigung, Parkausweis, BAf&ouml;G nach &sect;9 oder auch Bescheide, ob ihr euch f&uuml;r das n&auml;chste Semester r&uuml;ckgemeldet habt</li>
        <li><a href="https://www.qis.fh-aachen.de/qisserver/rds?state=user&amp;type=0">qis.fh-aachen.de</a> - Das QIS ist die &Uuml;bersichtsseite f&uuml;r die Klausur-anmeldungen/abmeldungen. Ebenso k&ouml;nnt ihr dort euren Notenschnitt und die Klausurergebnisse euch anschauen.</li>
        <li><a href="https://services.fh-aachen.de/">services.fh-aachen.de</a> - Auf dieser Seite kannst du deine FH Karte beantragen und weitere Informationen &uuml;ber dich erhalten.</li>
        <li><a href="https://www.fh-aachen.de/hochschule/datenverarbeitungszentrale/soft-und-hardware/microsoft/microsoft-azure">MS Azure (Office 365)</a> - Microsoft Azure bietet euch die M&ouml;glichkeit, als Studierende die Officeprodukte kostenlos zu nutzen.</li>
        <li><a href="https://www.ili.fh-aachen.de/ilias.php?baseClass=ilrepositorygui&amp;reloadpublic=1&amp;cmd=frameset&amp;ref_id=1">Illias FH Aachen</a> - Illias ist die Plattform, bei der ihr die Unterlagen zu den einzelnen Modulen erhaltet.</li>
        <li><a href="https://www.fh-aachen.de/fachbereiche/elektrotechnik-und-informationstechnik/direkteinstieg/fuer-studierende/termine-fristen-studierende">Wichtige Termine</a> - Die FH bietet ebenfalls immer eine &Uuml;bersicht von wichtigen Terminen. Wie beispielsweise der Beginn der Klausurphase.</li>
        <li><a href="https://www.fh-aachen.de/hochschule/datenverarbeitungszentrale/kommunikationsdienste/e-mail">FH Mail</a> - Nach der Einschreibung erhaltet ihr einen Mailaccount f&uuml;r euer Studium. Wir raten euch t&auml;glich oder mindestens einmal die Woche in euren Account reinzuschauen.</li>
        <li><a href="https://www.studierendenwerk-aachen.de/de/gastronomie/Speisepl%C3%A4ne.html">Mensa Wochenplan</a> - Einmal eine &Uuml;bersicht &uuml;ber das Essen in der Mensa.</li>
        <li><a href="https://www.fh-aachen.de/hochschule/datenverarbeitungszentrale/studium-und-lehre/sciebo">Sciebo FH Aachen</a> - Sciebo ist die Campuscloud, bei der jeder Studierende der FH kostenlos 30 GB Speichervolumen erh&auml;lt.</li>
        </ul>
        <hr />
        <h2>📹 Videosammlungen</h2>
        <table>
        <tbody>
        <tr>
        <td><strong>🏅 (HSZ) Hochschulsportzentrum:</strong></td>
        <td><strong>🏥 (PSB) Psychosoziale Beratung:</strong></td>
        <td><strong>🛎️ Servicestellen der FH Aachen:</strong></td>
        </tr>
        <tr>
        <td><iframe src="//www.youtube.com/embed/H93VuE6Um9A" allowfullscreen="allowfullscreen"></iframe></td>
        <td><iframe src="//www.youtube.com/embed/15cCJUFylDI" allowfullscreen="allowfullscreen"></iframe></td>
        <td><iframe src="//www.youtube.com/embed/KY4gxH5qpX8" allowfullscreen="allowfullscreen"></iframe></td>
        </tr>
        </tbody>
        </table>
        <h2>💡 Wichtige Abk&uuml;rzungen</h2>
        <ul>
        <li><a href="https://fsr5.fh-aachen.de/">FSR (Fachschaftsrat)</a> - Der Fachschaftsrat organisiert zusammen mit den Tutoren eure Erstiwoche und weitere Veranstaltungen. Dar&uuml;ber hinaus ist er euer erster Ansprechpartner, wenn ihr mal Probleme mit einem Prof habt, generelle Fragen zur Pr&uuml;fungsordnung (PO) oder einfach ein wenig quatschen wollt. Ihr findet den FSR in E034. Quatscht Sie sonst gerne auch in der Erstiwoche an (Ihr erkennt Sie an den Tutorenjacken mit dem FSR hinten drauf)</li>
        <li><a href="https://www.fh-aachen.de/downloads/fh-mitteilungen/pruefungsordnungen/elektrotechnik-und-informationstechnik">PO (Pr&uuml;fungsordnung)</a> - Die Pr&uuml;fungsordnung findet ihr hier. Dort stehen alle relevanten Informationen rund um euer Studium! Welche Bedingungen f&uuml;r welches Semester gelten, was man f&uuml;r ein Auslandssemester braucht und allgemeine Pr&uuml;fungsangelegenheiten. Bitte liest sie euch durch.</li>
        <li><a href="https://www.fh-aachen.de/downloads/fh-mitteilungen/pruefungsordnungen/rahmenpruefungsordnungen">RPO (Rahmenpr&uuml;fungsordnung)</a> - Die Rahmepr&uuml;fungsordnung enth&auml;lt die allgemeinen Teile zum Pr&uuml;fungsrecht und zum Pr&uuml;fungsverfahren.</li>
        <li><a href="https://asta.fh-aachen.org/">AStA (Allgemeiner Studierenden Ausschuss)</a> - Die Hauptaufgabe des AStA ist die ganzheitliche Interessenvertretung aller Studierenden der Hochschule. Wenn du Fragen zu deinem Semesterticket, BAf&Ouml;G,... hast, ist meister der AStA der beste Ansprechpartner. Auch ASteln k&ouml;nnt ihr in unserer Erstiwoche antreffen. Ihr erkennt sie an den wei&szlig;en Jacken mit dem AStA Logo.</li>
        <li><a href="https://esp.fh-aachen.org/">ESP (Erstsemesterprojekt)</a> - Das ESP ist ein studentisches Projekt, dass die Wochenendseminare f&uuml;r alle angehenden Erstsemestertutor:innen der Fachhochschule organisiert und moderiert. Also falls ihr ebenfalls Erstsemester-tutor oder tutorin werden wollt, werdet ihr mit ihnen in Kontakt kommen.</li>
        </ul>
        <hr />
        <h2>🏫 Euer zuk&uuml;nftiger Campus</h2>
        <p>Falls ihr die FH in der Erstiwoche nicht besuchen k&ouml;nnt und keine Eindr&uuml;cke des Campus bekommen konntet, k&ouml;nnt ihr das hier <a href="http://vrtour.fh-aachen.de/">virtuell</a> nachholen!🥽<br />Auch einzelne Professoren und Professorinnen stellen sich gerne euch vor. Hier f&uuml;r haben wir ebenfalls eine <a href="https://fh-aachen.sciebo.de/s/u7EvPozHdv470qJ?path=%2F%5B%20ET%20INF%20MCD%20WI%20%5D%20Profs">Linksammlung</a> vorbereitet. <br />Die Professoren und Professorinnen an der FH lernt man auch nat&uuml;rlich im Laufe des Studiums kennen, gerade an der FH tauschen Sie sich gerne mit den Studierenden aus und trinken mal das ein oder andere Bier auf Veranstaltungen des FSR\s mit.🍻</p>
        <p><br />Falls ihr weitere Veranstaltungen im Semester mit bekommen wollt, folgt am einfachsten dem FSR auf <a href="https://www.instagram.com/fsr5.fhaachen/">Insta</a>📷 oder schaut auf der <a href="https://fsr5.fh-aachen.de/">Website</a> vorbei. Sonst werdet ihr nat&uuml;rlich auch &uuml;ber euren Mailaccount informiert.</p>
        <hr />
        <h2>🔍 Sonstiges</h2>
        <p>Ihr habt noch offene Fragen und wir konnten nicht alles beantworten? Weitere Informationen erhaltet ihr in der Erstiwoche oder fragt einfach mal in eurer Telegramgruppe nach. Meist findet man auch schon die L&ouml;sung, wenn es fachspezifisch ist, wenn man Folgendes googelt: <strong>FB5 FH Aachen &lt;Suchbegriff&gt;</strong></p>
        ';

        // save the page
        $page->save();
    }
}
