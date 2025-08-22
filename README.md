# CZ Quotes

**CZ Quotes** è un plugin per WordPress che aggiunge un nuovo **Custom Post Type** chiamato **Citazioni**.  
Permette di gestire facilmente citazioni con campi personalizzati (*Autore della Citazione* e *Informazioni Aggiuntive*) e di mostrarle sul sito tramite lo shortcode `[zen_quotes]`.

---

## ✨ Funzionalità

- **Custom Post Type "Citazioni"**
  - Accessibile dal menu di WordPress con l'icona delle virgolette.
  - URL archivio dedicato: `/citazioni`.
  - Supporta titolo, contenuto, immagine in evidenza, excerpt.
  - Campi extra:
    - **Autore della Citazione**
    - **Informazioni Aggiuntive**

- **Shortcode `[zen_quotes]`**
  - Mostra una o più citazioni sul frontend.
  - Parametri disponibili:
    - `limit` *(default: 1)* → numero di citazioni da mostrare.
    - `frequency` *(default: refresh)* → modalità di selezione:
      - `refresh`: cambia ad ogni caricamento pagina.
      - `daily`: mantiene la stessa selezione per tutta la giornata.
    - `show` *(default: random)* → criterio di scelta:
      - `latest`: le ultime inserite (ordine cronologico).
      - `random`: casuali.

- **Stile incluso**
  - CSS già pronto (`css/cz-quotes.css`) per una resa grafica semplice e pulita.
  - Caricato automaticamente dal plugin.

---

## 🚀 Installazione

1. Scarica o clona questo repository dentro la cartella dei plugin:
   ```bash
   wp-content/plugins/cz-quotes
