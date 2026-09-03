# Procesy — jak u nás probíhá práce

> [← zpět na rozcestník](../)

Postupy, které se v týmu opakovaně vysvětlují: jak probíhá code review, jak se onboarduje nový člověk, co dělat při incidentu. **Juniorům** slouží jako návod v momentě, kdy do procesu poprvé vstoupí; **celému týmu** jako podklad, když se proces mění.

Na rozdíl od [návrhových vzorů](../SoftwareDesign/) nebo [modelů větvení](../GitWorkflows/) tady nejde o volbu mezi variantami, ale o **popis toho, jak něco funguje a proč zrovna takhle**. Proto má každý proces v dokumentu i sekci o tom, co se v něm nejčastěji kazí.

---

## Procesy

| Proces | Čeho se týká | Stav |
| ------ | ------------ | ---- |
| [**Code review**](CodeReview/) | Jak se změna dostane od autora přes recenzenta do hlavní větve | ✅ |
| [**Agile Manifesto**](AgileManifesto/) | Přepis původního textu z roku 2001 — čtyři hodnoty a dvanáct principů | ✅ |
| Onboarding | První dny nového člena týmu | ⬜ |
| Incident | Co dělat, když hoří produkce | ⬜ |
| Release | Jak se vydává a nasazuje | ⬜ |

<sub>⬜ plánováno · 🚧 rozpracováno · ✅ hotovo</sub>

---

## Čím se tyhle dokumenty řídí

**Popisují proces, ne názor.** Kde existuje veřejně ověřitelný podklad — výzkum, příručka velké firmy, zavedená konvence — dokument se o něj opře a odkáže na něj. Kde takový podklad není, je to řečeno nahlas.

**Nepíšou, „jak to děláme u nás“**, pokud to není ověřené a odsouhlasené. Popisují možnosti a jejich cenu; konkrétní nastavení týmu je samostatná věc, kterou musí někdo potvrdit — jinak by v dokumentaci vznikla pravidla, na kterých se nikdo nedohodl.

**Počítají s tím, že proces se dá udělat špatně.** Sekce o častých chybách má stejnou váhu jako popis toho, jak to má vypadat.

---

## Přidání nového procesu

Tvar dokumentu a pravidla psaní pro tuhle sekci: [`CLAUDE.md`](CLAUDE.md).

Ve zkratce: složka pojmenovaná anglicky, uvnitř `README.md` s popisem procesu. U rozsáhlejšího procesu se vyplatí rozdělit dokumenty **podle rolí** — každý čte v jiný moment a hledá jinou věc, jako u [code review](CodeReview/).
