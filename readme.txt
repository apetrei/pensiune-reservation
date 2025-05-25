 === Calendar Rezervări Pensiune ===
Contributors: apetrei
Donate link: https://example.com/donate
Tags: calendar, rezervari, pensiune, booking, admin
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.1.2
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin WordPress simplu pentru gestionarea manuală a rezervărilor pensiunii printr-un calendar interactiv.

== Description ==

**Calendar Rezervări Pensiune** este un plugin destinat proprietarilor de pensiuni, cabane și alte unități de cazare care vor să marcheze zilele ocupate simplu și rapid, folosind un calendar în zona de administrare.

Funcționalități principale:  
- Marcare zile ocupate (individual sau interval).  
- Ștergere zile ocupate cu un singur click.  
- Export în format CSV pentru raportare.  
- Calendar public vizibil prin shortcode.  
- Jurnal de modificări pentru monitorizare.

== Installation ==

1. Încarcă directorul pluginului în `/wp-content/plugins/`.  
2. Activează pluginul din meniul „Module”.  
3. Accesează „Rezervări Pensiune” în zona de administrare pentru gestionare.  
4. Folosește shortcode-ul `[pensiune_calendar_public]` pentru a afișa calendarul public.

== Frequently Asked Questions ==

= Cum adaug o zi ocupată? =  
Dă click pe ziua dorită în calendarul din admin.

= Pot selecta un interval de zile? =  
Da, selectează cu mouse-ul intervalul dorit și confirmă.

= Cum șterg o zi ocupată? =  
Click pe ziua ocupată pentru a o elimina.

= Pot exporta lista cu zile ocupate? =  
Da, folosește butonul „Exportă CSV”.

= Cum afișez calendarul pe site? =  
Adaugă shortcode-ul `[pensiune_calendar_public]` în pagini sau articole.

== Screenshots ==

1. Calendarul din admin cu zilele ocupate.  
2. Calendarul public afișat pe site.

== Changelog ==

= 1.1.2 =  
* Compatibilitate PHP 8.2.  
* Eliminare dependențe externe; toate fișierele sunt locale.  
* Îmbunătățiri de securitate și sanitizare cod.

= 1.1.1 =  
* Corecții fus orar.  
* Validare mai strictă date AJAX.  
* Protecție nonce pentru acțiuni AJAX.  
* Limitare cereri AJAX.  
* Export CSV îmbunătățit.

= 1.0.0 =  
* Lansare inițială.

== Upgrade Notice ==

Versiunea 1.1.2 aduce compatibilitate mai bună cu PHP 8.2 și elimină dependențele externe.

== License ==

Acest plugin este distribuit sub licența GPLv2 sau versiune ulterioară.  
Mai multe detalii: https://www.gnu.org/licenses/gpl-2.0.html
