<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('privacy_policies', function (Blueprint $table) {
            $table->id();
            $table->longText('content');
            $table->longText('content_en')->nullable();
            $table->timestamps();
        });

        // Single-row table: seed the row every environment (including
        // production, where only `migrate --force` runs) reads and edits
        // through the Filament admin page. `content` (Dutch) is the legally
        // leading version; `content_en` is a courtesy translation only -
        // see PrivacyPolicyResource. Content carried over from the
        // pre-split frontend's privacy policy page.
        DB::table('privacy_policies')->insert([
            'id' => 1,
            'content' => $this->defaultContent(),
            'content_en' => $this->defaultContentEn(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privacy_policies');
    }

    private function defaultContent(): string
    {
        return <<<'HTML'
        <h2>TL;DR (Te Lang; Niet Gelezen)</h2>
        <p>Geen zin om lappen tekst te lezen? Snappen we. Hier is de korte versie:</p>
        <ul>
        <li><strong>Wat we opslaan:</strong> Alleen je NAW-gegevens (Naam, Adres, Woonplaats) en tijdelijke tech-dingen (IP-adres en sessie-info).</li>
        <li><strong>Waarom:</strong> Zodat jij de community kunt gebruiken en de website werkt.</li>
        <li><strong>Verkopen we je data?</strong> Nee, nooit.</li>
        <li><strong>Cookies?</strong> Alleen de noodzakelijke om te zorgen dat je ingelogd blijft. Geen trackers.</li>
        <li><strong>Wegwezen?</strong> Wil je dat we je gegevens verwijderen? Stuur ons een mailtje &rarr; <a href="mailto:privacy@havencommunity.nl">privacy@havencommunity.nl</a>.</li>
        </ul>
        <h2>De uitgebreide versie</h2>
        <p><em>(voor als je het écht zeker wilt weten)</em></p>
        <p>Wij zijn <strong>Haven Community Crew</strong>. We vinden je privacy belangrijk. We zijn een community, geen datahandelaar. Hieronder lees je precies wat we wel en niet doen met je gegevens.</p>
        <h3>1. Wie zijn wij?</h3>
        <p>De verantwoordelijke voor de website is: <strong>Haven Community Crew</strong> <em>(vertegenwoordigd door Tim Kolijn)</em>, gevestigd aan <strong>Blauwe Loper 61</strong>.</p>
        <p>Vragen? Mail ons: <a href="mailto:privacyofficer@havencommunity.nl">privacyofficer@havencommunity.nl</a></p>
        <h3>2. Welke gegevens checken we?</h3>
        <p>We verzamelen zo min mogelijk. Alleen wat nodig is om de boel te laten draaien:</p>
        <ul>
        <li><strong>NAW-gegevens:</strong> Je naam, adres en woonplaats. Dit hebben we nodig om te weten wie er in de community zit (of om eventueel iets naar je op te sturen).</li>
        <li><strong>Reserveringen van gedeelde ruimtes:</strong> Foto's die gemaakt zijn van gedeelde ruimtes. Tijd en datum van de reservering.</li>
        <li><strong>Tech-data (IP &amp; Sessie):</strong> We slaan tijdelijk je IP-adres op en gebruiken sessie-informatie. Dit is puur technisch: zo onthoudt de site dat jij het bent als je doorklikt naar een volgende pagina.</li>
        </ul>
        <h3>3. Waarom hebben we dit nodig?</h3>
        <p>We gebruiken je gegevens voor deze doelen:</p>
        <ol>
        <li><strong>Toegang:</strong> Om je toegang te geven tot Havencommunity.nl.</li>
        <li><strong>Functionaliteit:</strong> Zodat de website niet crasht of vergeet wie je bent terwijl je surft.</li>
        <li><strong>Veiligheid:</strong> IP-adressen helpen ons om hackers of spammers buiten de deur te houden.</li>
        <li><strong>Reservaties:</strong> Om je toegang te geven tot reserveren van gedeelde ruimtes.</li>
        <li><strong>Berichtgeving:</strong> Zodat wij je op de hoogte kunnen houden van activiteiten.</li>
        </ol>
        <h3>4. Hoelang bewaren we het?</h3>
        <p>Niet langer dan nodig is.</p>
        <ul>
        <li><strong>NAW:</strong> Zolang je lid bent van de community. Zeg je je lidmaatschap op? Dan verwijderen we je gegevens.</li>
        <li><strong>IP &amp; Sessie:</strong> Dit wordt heel snel weer verwijderd. Sessie-info verdwijnt vaak direct als je je browser afsluit.</li>
        </ul>
        <h3>5. Delen we dit met anderen?</h3>
        <p>Nee, we verkopen niks door. Het enige moment dat "derden" jouw data zien, is puur technisch. Denk aan het hostingbedrijf waar onze servers draaien. Met hen hebben we afspraken gemaakt dat ze met hun poten van jouw data afblijven (een verwerkersovereenkomst). Verder behoudt Vestide het recht om toegang te blokkeren naar havencommunity.nl.</p>
        <h3>6. Hoe zit het met cookies?</h3>
        <p>We gebruiken geen irritante tracking cookies die je achtervolgen met reclames voor sneakers die je net hebt bekeken. We gebruiken alleen <strong>functionele cookies</strong>. Die zijn nodig om de site te laten werken (bijvoorbeeld om te onthouden dat je ingelogd bent).</p>
        <h3>7. Baas over eigen data</h3>
        <p>Jij blijft de eigenaar van je gegevens. Volgens de wet heb je het recht om:</p>
        <ul>
        <li>Te zien wat we van je hebben.</li>
        <li>Foutjes te corrigeren (als je verhuisd bent bijvoorbeeld).</li>
        <li>Alles te laten verwijderen (het "recht om vergeten te worden").</li>
        </ul>
        <p>Wil je dit? Stuur even een mailtje naar <a href="mailto:privacyofficer@havencommunity.nl">privacyofficer@havencommunity.nl</a>. We fixen het zo snel mogelijk voor je.</p>
        <h3>8. Beveiliging</h3>
        <p>We doen ons best om havencommunity.nl veilig te houden. We gebruiken beveiligde verbindingen (SSL/HTTPS) en zorgen dat niet zomaar iedereen bij de database kan. Heb je het idee dat er toch iets lekt? Meld het ons direct via de mail (<a href="mailto:privacyofficer@havencommunity.nl">privacyofficer@havencommunity.nl</a>).</p>
        HTML;
    }

    private function defaultContentEn(): string
    {
        return <<<'HTML'
        <h2>TL;DR (Too Long; Didn't Read)</h2>
        <p>Not in the mood for walls of text? We get it. Here's the short version:</p>
        <ul>
        <li><strong>What we store:</strong> Just your name, address and city, plus temporary technical bits (IP address and session info).</li>
        <li><strong>Why:</strong> So you can use the community and the website actually works.</li>
        <li><strong>Do we sell your data?</strong> No, never.</li>
        <li><strong>Cookies?</strong> Only the essential ones to keep you logged in. No trackers.</li>
        <li><strong>Want out?</strong> Want us to delete your data? Send us an email &rarr; <a href="mailto:privacy@havencommunity.nl">privacy@havencommunity.nl</a>.</li>
        </ul>
        <h2>The full version</h2>
        <p><em>(for when you want to know exactly where you stand)</em></p>
        <p>We are <strong>Haven Community Crew</strong>. We take your privacy seriously. We're a community, not a data broker. Below you'll find exactly what we do and don't do with your data.</p>
        <h3>1. Who are we?</h3>
        <p>The party responsible for this website is: <strong>Haven Community Crew</strong> <em>(represented by Tim Kolijn)</em>, based at <strong>Blauwe Loper 61</strong>.</p>
        <p>Questions? Email us: <a href="mailto:privacyofficer@havencommunity.nl">privacyofficer@havencommunity.nl</a></p>
        <h3>2. What data do we collect?</h3>
        <p>We collect as little as possible - only what's needed to keep things running:</p>
        <ul>
        <li><strong>Name and address details:</strong> Your name, address and city. We need this to know who's part of the community (and to send you something if needed).</li>
        <li><strong>Shared space bookings:</strong> Photos taken of shared spaces. The time and date of the booking.</li>
        <li><strong>Technical data (IP &amp; session):</strong> We temporarily store your IP address and use session information. This is purely technical: it's how the site remembers who you are as you click through to another page.</li>
        </ul>
        <h3>3. Why do we need this?</h3>
        <p>We use your data for these purposes:</p>
        <ol>
        <li><strong>Access:</strong> To give you access to Havencommunity.nl.</li>
        <li><strong>Functionality:</strong> So the website doesn't crash or forget who you are while you browse.</li>
        <li><strong>Security:</strong> IP addresses help us keep hackers and spammers out.</li>
        <li><strong>Bookings:</strong> To give you access to booking shared spaces.</li>
        <li><strong>Communication:</strong> So we can keep you informed about activities.</li>
        </ol>
        <h3>4. How long do we keep it?</h3>
        <p>No longer than necessary.</p>
        <ul>
        <li><strong>Name and address details:</strong> For as long as you're a member of the community. Cancel your membership and we delete your data.</li>
        <li><strong>IP &amp; session:</strong> This is deleted again very quickly. Session info often disappears the moment you close your browser.</li>
        </ul>
        <h3>5. Do we share this with others?</h3>
        <p>No, we don't sell anything on. The only time "third parties" see your data is purely technical - think of the hosting company running our servers. We've made agreements with them to keep their hands off your data (a data processing agreement). Vestide also retains the right to block access to havencommunity.nl.</p>
        <h3>6. What about cookies?</h3>
        <p>We don't use annoying tracking cookies that follow you around with ads for sneakers you just looked at. We only use <strong>functional cookies</strong>. Those are needed to make the site work (for example, to remember that you're logged in).</p>
        <h3>7. You're in charge of your own data</h3>
        <p>You remain the owner of your data. By law, you have the right to:</p>
        <ul>
        <li>See what we have on you.</li>
        <li>Correct mistakes (if you've moved, for example).</li>
        <li>Have everything deleted (the "right to be forgotten").</li>
        </ul>
        <p>Want this? Just send an email to <a href="mailto:privacyofficer@havencommunity.nl">privacyofficer@havencommunity.nl</a>. We'll sort it out for you as quickly as possible.</p>
        <h3>8. Security</h3>
        <p>We do our best to keep havencommunity.nl secure. We use secured connections (SSL/HTTPS) and make sure not just anyone can get into the database. Think something's leaked anyway? Report it to us directly by email (<a href="mailto:privacyofficer@havencommunity.nl">privacyofficer@havencommunity.nl</a>).</p>
        HTML;
    }
};
