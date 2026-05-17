# Draft Prac do Zrobienia (Ogólny zakres)

Poniżej znajduje się lista zaplanowanych zadań i modyfikacji do wdrożenia w systemie:

## 1. Zarządzanie Kontami
- [ ] **Aktywacja/Dezaktywacja konta**: 
  - Administrator w widoku edycji użytkownika może włączyć lub wyłączyć konto.
  - Zablokowanie (dezaktywacja) konta uniemożliwia logowanie się do systemu przez danego użytkownika.

## 2. Nowa Kategoria "Uprawnienia"
Utworzenie nowej sekcji/kategorii z trzema głównymi podkategoriami: **Dostęp**, **Moduły** oraz **Operacje**.

### Podkategoria: Moduły (Refaktoryzacja)
- [ ] Zrefaktoryzowanie dwóch istniejących tabel (`P_program` i `P_modul`) i połączenie ich w jedną tabelę: `P_modul`.
- [ ] Nowa struktura tabeli `P_modul`: `id`, `nazwa`, `pozycja`.
- [ ] **Zarządzanie modułami (programami)**: Widok pozwalający na dodawanie, edycję i usuwanie modułów/programów.
- [ ] **Struktura zagnieżdżona**: Pole `pozycja` odpowiada za hierarchię (np. `0` = główna kategoria jak 'ERP', `1` = podkategoria jak 'moduł magazynowy').

### Podkategoria: Operacje
- [ ] Utworzenie dedykowanego widoku w postaci listy, służącego do dodawania, edycji i usuwania operacji.
- [ ] Oparcie operacji o tabelę bazy danych `P_operacje`.
- [ ] **Seeder**: Przygotowanie seedera uzupełniającego bazę domyślnymi akcjami: *tworzenie*, *edytowanie*, *usuwanie*.

### Podkategoria: Dostęp
- [ ] Zarządzanie uprawnieniami użytkowników: Widok z listą umożliwiający przypisywanie, edycję i usuwanie dostępów do konkretnych Modułów i Operacji dla danych użytkowników (prawdopodobnie w oparciu o tabelę np. `P_access`).

## 3. Zmiany w Uprawnieniach dla Ról
- [ ] **Rola: Moderator (mod)**:
  - Ograniczenie widoczności na liście użytkowników – moderator widzi wyłącznie osoby przypisane do tego samego wydziału co on.
  - Widoczność dostępów – moderator może przeglądać jakie uprawnienia mają użytkownicy w jego wydziale (na podstawie tabeli np. `P_access`), ale **nie ma prawa** ich edytować.
- [ ] **Rola: Użytkownik (user)**:
  - Wyświetlanie tabeli lub listy z posiadanymi uprawnieniami bezpośrednio na własnym pulpicie (dashboard).
  - Możliwość edycji (zmiany) własnego hasła z poziomu konta. 
