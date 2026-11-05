<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The FAQ's starting content, so every environment (production only runs
 * `migrate --force`) opens with a filled-in page that admins then edit in
 * Filament. Skipped when an admin already added categories.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('faq_categories')->exists()) {
            return;
        }

        $now = now();

        foreach (json_decode(self::CONTENT, true) as $categoryOrder => $category) {
            $categoryId = DB::table('faq_categories')->insertGetId([
                'title' => $category['title'],
                'title_en' => $category['title_en'],
                'icon' => $category['icon'],
                'flowchart' => $category['flowchart'] ? json_encode($category['flowchart']) : null,
                'sort_order' => $categoryOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($category['questions'] as $questionOrder => $question) {
                DB::table('faq_questions')->insert([
                    ...$question,
                    'faq_category_id' => $categoryId,
                    'is_published' => true,
                    'sort_order' => $questionOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Content only - dropping the tables is the schema migration's job.
    }

    private const CONTENT = <<<'JSON'
[
    {
        "title": "Aan de slag",
        "title_en": "Getting started",
        "icon": "👋",
        "flowchart": [
            {
                "icon": "🔑",
                "title": "Log in",
                "title_en": "Log in",
                "detail": "Met je Haven-account.",
                "detail_en": "With your Haven account.",
                "tone": "default",
                "url": null,
                "branches": []
            },
            {
                "icon": "📜",
                "title": "Accepteer het privacybeleid",
                "title_en": "Accept the privacy policy",
                "detail": "Eén keer, en opnieuw als het verandert.",
                "detail_en": "Asked once, and again when it changes.",
                "tone": "default",
                "url": null,
                "branches": []
            },
            {
                "icon": "🏷️",
                "title": "Vul je registratiecode in",
                "title_en": "Enter your registration code",
                "detail": "Koppelt je account aan je woning - scan de QR-code of typ hem in.",
                "detail_en": "Links your account to your unit - scan the QR code or type it in.",
                "tone": "default",
                "url": null,
                "branches": []
            },
            {
                "icon": "✅",
                "title": "Je bent binnen",
                "title_en": "You're in",
                "detail": "Reserveer ruimtes, bekijk de agenda en meer.",
                "detail_en": "Book rooms, see the agenda and more.",
                "tone": "good",
                "url": null,
                "branches": []
            }
        ],
        "questions": [
            {
                "question": "Wat is de Haven-app?",
                "question_en": "What is the Haven app?",
                "answer": "<p>De app voor bewoners van Haven. Je reserveert er de gedeelde ruimtes mee, checkt in en uit bij je reserveringen en houdt de agenda bij.</p>",
                "answer_en": "<p>The app for residents of Haven. You use it to book the shared rooms, check in and out of your reservations, and keep up with the agenda.</p>"
            },
            {
                "question": "Waar krijg ik mijn registratiecode?",
                "question_en": "Where do I get my registration code?",
                "answer": "<p>Elke woning heeft een eigen registratiecode, meestal als QR-code. Scan hem of typ hem in na het inloggen om je account aan je woning te koppelen. Een code werkt maar één keer - werkt de jouwe niet, vraag het Haven-team dan om een nieuwe.</p>",
                "answer_en": "<p>Every unit has its own registration code, usually as a QR code. Scan it or type it in after logging in to link your account to your unit. A code can only be used once - if yours doesn't work, ask the Haven team for a new one.</p>"
            },
            {
                "question": "Waarom moet ik het privacybeleid accepteren?",
                "question_en": "Why do I have to accept the privacy policy?",
                "answer": "<p>Daarin staat wat Haven met je gegevens doet. Je moet het accepteren voordat je de app kunt gebruiken, en je krijgt de vraag opnieuw als er een nieuwe versie is.</p>",
                "answer_en": "<p>It explains what Haven does with your data. You need to accept it before you can use the app, and you'll be asked again whenever a new version comes out.</p>"
            }
        ]
    },
    {
        "title": "Reserveringen",
        "title_en": "Reservations",
        "icon": "🎟️",
        "flowchart": [
            {
                "icon": "🚪",
                "title": "Kies een ruimte",
                "title_en": "Pick a room",
                "detail": "Onder Ruimtes.",
                "detail_en": "Under Rooms.",
                "tone": "default",
                "url": "/dashboard/rooms",
                "branches": []
            },
            {
                "icon": "🕗",
                "title": "Kies een vrij tijdslot",
                "title_en": "Pick a free time slot",
                "detail": "Nieuwe dagen gaan om 20:00 de avond ervoor open.",
                "detail_en": "New days unlock at 20:00 the evening before.",
                "tone": "default",
                "url": null,
                "branches": []
            },
            {
                "icon": "📧",
                "title": "Reservering bevestigd",
                "title_en": "Reservation confirmed",
                "detail": "Je krijgt een bevestigingsmail.",
                "detail_en": "You get a confirmation email.",
                "tone": "good",
                "url": null,
                "branches": []
            },
            {
                "icon": "🤔",
                "title": "Ga je nog?",
                "title_en": "Are you still going?",
                "detail": null,
                "detail_en": null,
                "tone": "default",
                "url": null,
                "branches": [
                    {
                        "label": "Annuleren",
                        "label_en": "Cancel",
                        "steps": [
                            {
                                "icon": "❌",
                                "title": "Annuleer hem",
                                "title_en": "Cancel it",
                                "detail": "Kan tot de reservering begint.",
                                "detail_en": "Possible until the reservation starts.",
                                "tone": "wait",
                                "url": null,
                                "branches": []
                            }
                        ]
                    },
                    {
                        "label": "Gaan",
                        "label_en": "Go",
                        "steps": [
                            {
                                "icon": "📷",
                                "title": "In- en uitchecken",
                                "title_en": "Check in and out",
                                "detail": "Met foto's - zie hieronder.",
                                "detail_en": "With photos - see below.",
                                "tone": "default",
                                "url": "/dashboard/reservations",
                                "branches": []
                            }
                        ]
                    }
                ]
            }
        ],
        "questions": [
            {
                "question": "Hoe reserveer ik een ruimte?",
                "question_en": "How do I book a room?",
                "answer": "<p>Ga naar <a href=\"/dashboard/rooms\">Ruimtes</a>, open de ruimte die je wilt en kies een vrij tijdslot in het rooster. Geef de reservering een naam en bevestig. Je krijgt een bevestigingsmail.</p>",
                "answer_en": "<p>Go to <a href=\"/dashboard/rooms\">Rooms</a>, open the room you want and pick a free time slot in its schedule. Give the reservation a name and confirm. You'll get a confirmation email.</p>"
            },
            {
                "question": "Wanneer kan ik de volgende dag reserveren?",
                "question_en": "When can I book the next day?",
                "answer": "<p>De tijdsloten van de volgende dag gaan om 20:00 de avond ervoor open, niet om middernacht.</p>",
                "answer_en": "<p>The next day's time slots unlock at 20:00 the evening before, not at midnight.</p>"
            },
            {
                "question": "Hoe ver vooruit kan ik reserveren?",
                "question_en": "How far ahead can I book?",
                "answer": "<p>Dat verschilt per ruimte. In het weekrooster van elke ruimte zie je welke tijden je kunt reserveren en hoe ver vooruit.</p>",
                "answer_en": "<p>That differs per room. Each room's weekly schedule shows which times you can book and how far ahead.</p>"
            },
            {
                "question": "Er staat dat de ruimte al gereserveerd is. Waarom?",
                "question_en": "It says the room is already booked. Why?",
                "answer": "<p>Iemand anders heeft een reservering die overlapt met de tijd die je koos. Een ruimte kan maar door één persoon tegelijk gereserveerd worden - kies een ander tijdslot.</p>",
                "answer_en": "<p>Someone else has a reservation that overlaps with the time you picked. A room can only be booked by one person at a time - pick another slot.</p>"
            },
            {
                "question": "Wat doet \"Naam delen met andere bewoners\"?",
                "question_en": "What does \"Share name with other residents\" do?",
                "answer": "<p>Zet je het aan, dan zien andere Haven-bewoners wie de ruimte gereserveerd heeft. Het wordt alleen met bewoners gedeeld. Laat je het uit, dan zien ze alleen dat de ruimte bezet is.</p>",
                "answer_en": "<p>If you turn it on, other Haven residents can see who booked the room. It's only shared with residents. If you leave it off, they only see that the room is taken.</p>"
            },
            {
                "question": "Kan ik mijn reservering wijzigen?",
                "question_en": "Can I change my reservation?",
                "answer": "<p>Je kunt de naam wijzigen. Wil je een andere tijd of ruimte, annuleer dan en maak een nieuwe reservering.</p>",
                "answer_en": "<p>You can change its name. To change the time or room, cancel it and make a new reservation.</p>"
            },
            {
                "question": "Hoe annuleer ik een reservering?",
                "question_en": "How do I cancel a reservation?",
                "answer": "<p>Onder Reserveringen, voordat hij begint. Is de reservering al begonnen, of heb je al ingecheckt, dan kan hij niet meer geannuleerd worden. Je krijgt een mail als hij geannuleerd is.</p>",
                "answer_en": "<p>Under Reservations, cancel it before it starts. Once a reservation has started, or once you've checked in, it can't be cancelled anymore. You'll get an email when it's cancelled.</p>"
            },
            {
                "question": "Waar zie ik mijn reserveringen?",
                "question_en": "Where can I see my reservations?",
                "answer": "<p>Onder <a href=\"/dashboard/reservations\">Reserveringen</a>. Daar check je ook in en uit, en zie je of je foto's zijn goedgekeurd.</p>",
                "answer_en": "<p>Under <a href=\"/dashboard/reservations\">Reservations</a>. That's also where you check in and out, and where you see whether your photos were approved.</p>"
            }
        ]
    },
    {
        "title": "In- en uitchecken",
        "title_en": "Check-in & check-out",
        "icon": "📷",
        "flowchart": [
            {
                "icon": "📸",
                "title": "Check in met foto's",
                "title_en": "Check in with photos",
                "detail": "Vanaf 15 minuten voor de start tot het einde.",
                "detail_en": "From 15 minutes before the start until the end.",
                "tone": "default",
                "url": "/dashboard/reservations",
                "branches": []
            },
            {
                "icon": "🛋️",
                "title": "Gebruik de ruimte",
                "title_en": "Use the room",
                "detail": "Veel plezier!",
                "detail_en": "Enjoy!",
                "tone": "default",
                "url": null,
                "branches": []
            },
            {
                "icon": "🧹",
                "title": "Ruim op",
                "title_en": "Clean up",
                "detail": "Laat de ruimte achter zoals je hem aantrof.",
                "detail_en": "Leave the room as you found it.",
                "tone": "default",
                "url": null,
                "branches": []
            },
            {
                "icon": "⏱️",
                "title": "Binnen 1 uur na het einde uitgecheckt met foto's?",
                "title_en": "Checked out with photos within 1 hour after the end?",
                "detail": null,
                "detail_en": null,
                "tone": "default",
                "url": null,
                "branches": [
                    {
                        "label": "Ja",
                        "label_en": "Yes",
                        "steps": [
                            {
                                "icon": "👀",
                                "title": "Wacht op beoordeling",
                                "title_en": "Pending review",
                                "detail": "Iemand van Haven bekijkt je foto's.",
                                "detail_en": "Someone from Haven looks at your photos.",
                                "tone": "wait",
                                "url": null,
                                "branches": []
                            },
                            {
                                "icon": "⚖️",
                                "title": "Is de ruimte goed achtergelaten?",
                                "title_en": "Was the room left OK?",
                                "detail": null,
                                "detail_en": null,
                                "tone": "default",
                                "url": null,
                                "branches": [
                                    {
                                        "label": "Ziet er goed uit",
                                        "label_en": "Looks good",
                                        "steps": [
                                            {
                                                "icon": "✅",
                                                "title": "Goedgekeurd",
                                                "title_en": "Verified",
                                                "detail": "Helemaal klaar.",
                                                "detail_en": "All done.",
                                                "tone": "good",
                                                "url": null,
                                                "branches": []
                                            }
                                        ]
                                    },
                                    {
                                        "label": "Niet goed",
                                        "label_en": "Not OK",
                                        "steps": [
                                            {
                                                "icon": "📧",
                                                "title": "Afgekeurd",
                                                "title_en": "Rejected",
                                                "detail": "Je krijgt een mail met de reden.",
                                                "detail_en": "You get an email with the reason.",
                                                "tone": "bad",
                                                "url": null,
                                                "branches": []
                                            }
                                        ]
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "label": "Nee",
                        "label_en": "No",
                        "steps": [
                            {
                                "icon": "🚫",
                                "title": "Automatisch afgekeurd",
                                "title_en": "Automatically rejected",
                                "detail": "Er wordt aangenomen dat de ruimte niet goed is achtergelaten. Je krijgt een mail.",
                                "detail_en": "The room is assumed not to have been left OK. You get an email.",
                                "tone": "bad",
                                "url": null,
                                "branches": []
                            },
                            {
                                "icon": "💬",
                                "title": "Herinnering in de app",
                                "title_en": "Reminder in the app",
                                "detail": "De volgende keer dat je de app opent.",
                                "detail_en": "Next time you open the app.",
                                "tone": "bad",
                                "url": null,
                                "branches": []
                            }
                        ]
                    }
                ]
            }
        ],
        "questions": [
            {
                "question": "Waarom moet ik foto's maken?",
                "question_en": "Why do I have to take photos?",
                "answer": "<p>De foto's laten zien in welke staat de ruimte was. Je incheckfoto's laten zien hoe je hem aantrof, zodat jij niet de schuld krijgt van rommel van iemand anders. Je uitcheckfoto's laten zien dat je hem schoon hebt achtergelaten.</p>",
                "answer_en": "<p>The photos show what state the room was in. Your check-in photos show how you found it, so you can't be blamed for a mess someone else left. Your check-out photos show you left it clean.</p>"
            },
            {
                "question": "Wanneer kan ik inchecken?",
                "question_en": "When can I check in?",
                "answer": "<p>Vanaf 15 minuten voor je reservering begint tot hij afloopt. Na het einde van de reservering kun je niet meer inchecken.</p>",
                "answer_en": "<p>From 15 minutes before your reservation starts until it ends. After the reservation has ended, you can't check in anymore.</p>"
            },
            {
                "question": "Wanneer kan ik uitchecken?",
                "question_en": "When can I check out?",
                "answer": "<p>Nadat je hebt ingecheckt, tot 1 uur na het einde van je reservering. Je moet eerst inchecken voordat je kunt uitchecken.</p>",
                "answer_en": "<p>Once you've checked in, until 1 hour after your reservation ends. You need to check in before you can check out.</p>"
            },
            {
                "question": "Hoeveel foto's kan ik uploaden?",
                "question_en": "How many photos can I upload?",
                "answer": "<p>1 tot 5 foto's per in- of uitcheck, maximaal 10 MB per foto (JPEG, PNG of WebP). Maak er een paar vanuit verschillende hoeken, zodat de hele ruimte te zien is.</p>",
                "answer_en": "<p>1 to 5 photos per check-in or check-out, up to 10 MB each (JPEG, PNG or WebP). Take a few angles so the whole room is visible.</p>"
            },
            {
                "question": "Wat is de voorbeeldfoto?",
                "question_en": "What is the example photo?",
                "answer": "<p>Sommige ruimtes hebben een voorbeeldfoto die laat zien hoe de ruimte eruit hoort te zien. Maak je foto's vanuit dezelfde hoek, zodat ze makkelijk te vergelijken zijn.</p>",
                "answer_en": "<p>Some rooms have an example photo showing how the room should look. Take your photos from the same angle so they're easy to compare.</p>"
            },
            {
                "question": "Wat moet er op de foto's staan?",
                "question_en": "What should be in the photos?",
                "answer": "<p>De hele ruimte: tafels, vloer, aanrecht en alles wat je gebruikt hebt. Zorg dat de foto's scherp en goed belicht zijn.</p>",
                "answer_en": "<p>The whole room: tables, floor, kitchen counters and anything you used. Make sure the photos are clear and well lit.</p>"
            },
            {
                "question": "Wat gebeurt er na het uploaden?",
                "question_en": "What happens after I upload?",
                "answer": "<p>Je check staat op \"Wacht op beoordeling\" tot iemand van Haven ernaar kijkt. Daarna wordt hij \"Goedgekeurd\" of \"Afgekeurd\". Je ziet de status onder Reserveringen.</p>",
                "answer_en": "<p>Your check is \"Pending review\" until someone from Haven looks at it. It then becomes \"Verified\" or \"Rejected\". You can see the status under Reservations.</p>"
            },
            {
                "question": "Mijn check is afgekeurd. Wat nu?",
                "question_en": "My check was rejected. What now?",
                "answer": "<p>Je krijgt een mail met de reden, en de reden staat ook bij je reservering. Lees hem goed, zodat het de volgende keer niet weer gebeurt.</p>",
                "answer_en": "<p>You get an email with the reason, and the reason is also shown with your reservation. Read it carefully so it doesn't happen next time.</p>"
            },
            {
                "question": "Wat als ik vergeet uit te checken?",
                "question_en": "What if I forget to check out?",
                "answer": "<p>Is er binnen 1 uur na het einde van je reservering geen uitcheck ingediend, dan wordt hij automatisch afgekeurd: er wordt aangenomen dat de ruimte niet goed is achtergelaten. Je krijgt daar een mail over.</p>",
                "answer_en": "<p>If no check-out is submitted within 1 hour after your reservation ends, it's automatically rejected: the room is assumed not to have been left OK. You get an email about it.</p>"
            },
            {
                "question": "Waarom kreeg ik een melding over ontbrekende foto's?",
                "question_en": "Why did I get a popup about missing photos?",
                "answer": "<p>Bij een van je eerdere reserveringen zijn geen in- of uitcheckfoto's geüpload. De melding laat zien welke, als herinnering voor de volgende keer. Tik op \"Begrepen\" en hij verschijnt niet meer voor die reserveringen.</p>",
                "answer_en": "<p>One of your past reservations has no check-in or check-out photos. The popup shows which ones, as a reminder for next time. Tap \"Got it\" and it won't show again for those reservations.</p>"
            },
            {
                "question": "Wie ziet mijn foto's?",
                "question_en": "Who sees my photos?",
                "answer": "<p>Het Haven-team beoordeelt ze. Je uitcheckfoto's en hoe de ruimte is achtergelaten worden ook gedeeld met Vestide, je verhuurder.</p>",
                "answer_en": "<p>The Haven team reviews them. Your check-out photos and how the room was left are also shared with Vestide, your landlord.</p>"
            }
        ]
    },
    {
        "title": "Ruimtes & huisregels",
        "title_en": "Rooms & house rules",
        "icon": "🏠",
        "flowchart": null,
        "questions": [
            {
                "question": "Welke ruimtes kan ik reserveren?",
                "question_en": "Which rooms can I book?",
                "answer": "<p>Alle ruimtes die je kunt reserveren staan onder <a href=\"/dashboard/rooms\">Ruimtes</a>, met een beschrijving en waar ze in het gebouw zijn.</p>",
                "answer_en": "<p>All rooms you can book are under <a href=\"/dashboard/rooms\">Rooms</a>, each with a description and its location in the building.</p>"
            },
            {
                "question": "Waarom kan ik sommige tijden niet reserveren?",
                "question_en": "Why can't I book certain times?",
                "answer": "<p>Elke ruimte heeft een eigen weekrooster met de tijden waarop hij te reserveren is. Tijden daarbuiten, tijden te ver vooruit en tijden die iemand anders al gereserveerd heeft, kun je niet kiezen.</p>",
                "answer_en": "<p>Each room has its own weekly schedule with the times it can be booked. Times outside it, times too far ahead, and times someone else has already booked can't be picked.</p>"
            },
            {
                "question": "Hoe laat ik de ruimte achter?",
                "question_en": "How should I leave the room?",
                "answer": "<p>Schoon en netjes, zoals je hem aantrof - of zoals op de voorbeeldfoto. Ruim op wat je gebruikt hebt, neem je spullen en afval mee en check daarna uit met foto's.</p>",
                "answer_en": "<p>Clean and tidy, the way you found it - or the way the example photo shows. Clean up what you used, take your things and your trash, and then check out with photos.</p>"
            },
            {
                "question": "De ruimte was vies of er was iets kapot toen ik aankwam.",
                "question_en": "The room was dirty or something was broken when I arrived.",
                "answer": "<p>Zorg dat het duidelijk op je incheckfoto's staat, zodat vastligt dat het al zo was. Laat het daarna het Haven-team weten.</p>",
                "answer_en": "<p>Make sure it's clearly in your check-in photos, so it's on record that it was already like that. Then let the Haven team know.</p>"
            }
        ]
    },
    {
        "title": "Account & privacy",
        "title_en": "Account & privacy",
        "icon": "👤",
        "flowchart": null,
        "questions": [
            {
                "question": "Hoe verander ik de taal of de donkere modus?",
                "question_en": "How do I change the language or dark mode?",
                "answer": "<p>Op je <a href=\"/dashboard/profile\">profielpagina</a> (tik op je initialen). Je keuze wordt in je account opgeslagen, dus geldt ook op je andere apparaten.</p>",
                "answer_en": "<p>On your <a href=\"/dashboard/profile\">profile page</a> (tap your initials). Your choice is saved to your account, so it also applies on your other devices.</p>"
            },
            {
                "question": "Welke mails krijg ik?",
                "question_en": "Which emails will I get?",
                "answer": "<p>Een mail als je een reservering maakt of annuleert, en als een van je in- of uitcheckfoto's is afgekeurd.</p>",
                "answer_en": "<p>An email when you make or cancel a reservation, and when one of your check-in or check-out photos is rejected.</p>"
            },
            {
                "question": "Wat gebeurt er als ik verhuis?",
                "question_en": "What happens when I move out?",
                "answer": "<p>Je account wordt losgekoppeld van je woning. 30 dagen later worden je persoonsgegevens gewist. Je eerdere reserveringen blijven anoniem in het systeem - niets daarin is nog naar jou te herleiden.</p>",
                "answer_en": "<p>Your account is unlinked from your unit. 30 days later your personal data is erased. Your past reservations stay in the system anonymously - nothing in them can identify you anymore.</p>"
            },
            {
                "question": "Wat doet Haven met mijn gegevens?",
                "question_en": "What does Haven do with my data?",
                "answer": "<p>Dat staat in het <a href=\"/privacy-policy\">privacybeleid</a> dat je bij je eerste keer inloggen hebt geaccepteerd.</p>",
                "answer_en": "<p>That's explained in the <a href=\"/privacy-policy\">privacy policy</a> you accepted when you first logged in.</p>"
            }
        ]
    },
    {
        "title": "De app",
        "title_en": "The app",
        "icon": "📱",
        "flowchart": null,
        "questions": [
            {
                "question": "Waar kan ik Haven gebruiken?",
                "question_en": "Where can I use Haven?",
                "answer": "<p>In je browser op havencommunity.nl, en als app op iPhone en Android.</p>",
                "answer_en": "<p>In your browser at havencommunity.nl, and as an app on iPhone and Android.</p>"
            },
            {
                "question": "Mijn foto's willen niet uploaden.",
                "question_en": "My photos won't upload.",
                "answer": "<p>Controleer je internetverbinding en probeer het opnieuw. Zijn je foto's te groot, upload er dan minder tegelijk. Uploaden via mobiele data kan even duren - houd de app open tot het klaar is.</p>",
                "answer_en": "<p>Check your internet connection and try again. If your photos are too large, upload fewer at once. Uploading on mobile data can take a while - keep the app open until it's done.</p>"
            },
            {
                "question": "Welke versie van de app heb ik?",
                "question_en": "Which version of the app do I have?",
                "answer": "<p>De versie staat onderaan het menu. Noem hem als je een probleem meldt.</p>",
                "answer_en": "<p>The version is shown at the bottom of the menu. Mention it when you report a problem.</p>"
            },
            {
                "question": "Mijn vraag staat er niet tussen.",
                "question_en": "My question isn't here.",
                "answer": "<p>Vraag het Haven-team - we helpen je graag.</p>",
                "answer_en": "<p>Ask the Haven team - we're happy to help.</p>"
            }
        ]
    }
]
JSON;
};
