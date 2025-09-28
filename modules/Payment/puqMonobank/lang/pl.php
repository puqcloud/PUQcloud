<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Dmytro Kravchenko <dmytro@kravchenko.im>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

return [
    // General
    'Pay with Monobank' => 'Zapłać przez Monobank',
    'Secure payment powered by Monobank' => 'Bezpieczna płatność obsługiwana przez Monobank',
    'Monobank Configuration' => 'Konfiguracja Monobank',
    
    // Configuration Page
    'API Configuration' => 'Konfiguracja API',
    'Production Token' => 'Token produkcyjny',
    'Enter your production API token from Monobank merchant panel' => 'Wprowadź produkcyjny token API z panelu sprzedawcy Monobank',
    'Get your token from' => 'Pobierz token z',
    'Enable Sandbox Mode' => 'Włącz tryb sandbox',
    'Use for testing. Get test token from' => 'Używane do testów. Pobierz testowy token z',
    'Sandbox Token' => 'Token sandbox',
    'Enter your test API token' => 'Wprowadź testowy token API',
    
    'Payment Settings' => 'Ustawienia płatności',
    'Payment Timeout (seconds)' => 'Limit czasu płatności (sekundy)',
    'Time limit for payment completion (5 minutes to 24 hours)' => 'Limit czasu na zakończenie płatności (od 5 minut do 24 godzin)',
    'Payment Type' => 'Typ płatności',
    'Debit (Immediate payment)' => 'Debet (płatność natychmiastowa)',
    'Hold (Authorize and capture later)' => 'Hold (autoryzacja i późniejsze obciążenie)',
    'Hold payments require manual finalization within 9 days' => 'Płatności typu hold wymagają ręcznego sfinalizowania w ciągu 9 dni',
    'Enable iFrame Mode' => 'Włącz tryb iFrame',
    'Display payment form in iframe instead of redirect' => 'Wyświetlaj formularz płatności w iframe zamiast przekierowania',
    'Auto Redirect to Payment' => 'Automatyczne przekierowanie do płatności',
    'Automatically redirect customers to payment page' => 'Automatycznie przekierowuj klientów na stronę płatności',
    
    'Advanced Settings' => 'Ustawienia zaawansowane',
    'CMS Name' => 'Nazwa CMS',
    'Used for analytics in Monobank dashboard' => 'Używane do analityki w panelu Monobank',
    'CMS Version' => 'Wersja CMS',
    'Enable Detailed Logging' => 'Włącz szczegółowe logowanie',
    'Log all API requests and responses for debugging' => 'Loguj wszystkie żądania i odpowiedzi API do debugowania',
    
    'Webhook Configuration' => 'Konfiguracja webhook',
    'Webhook URL' => 'URL webhook',
    'Copy' => 'Kopiuj',
    'Copied' => 'Skopiowano!',
    'Configure this URL in your Monobank merchant panel to receive payment notifications' => 'Skonfiguruj ten adres URL w panelu sprzedawcy Monobank, aby otrzymywać powiadomienia o płatnościach',
    
    'Webhook Security' => 'Bezpieczeństwo webhook',
    'Enable Webhook Signature Verification' => 'Włącz weryfikację podpisu webhook',
    'Verify webhook signatures to ensure authenticity (recommended)' => 'Weryfikuj podpisy webhook w celu zapewnienia autentyczności (zalecane)',
    'Webhook Public Key' => 'Klucz publiczny webhook',
    'Enter your webhook public key from Monobank' => 'Wprowadź publiczny klucz webhook z Monobank',
    'Get your webhook public key from' => 'Pobierz publiczny klucz webhook z',
    'This key is used to verify webhook signatures for security' => 'Ten klucz służy do weryfikacji podpisów webhook ze względów bezpieczeństwa',
    'Public key is currently configured' => 'Klucz publiczny jest obecnie skonfigurowany',
    'Leave empty to keep current key' => 'Pozostaw puste, aby zachować obecny klucz',
    'Fetch public key from Monobank API' => 'Pobierz klucz publiczny z API Monobank',
    'Click the download button to automatically fetch the key from Monobank API' => 'Kliknij przycisk pobierania, aby automatycznie pobrać klucz z API Monobank',
    'Public key fetched successfully' => 'Pomyślnie pobrano klucz publiczny',
    'Failed to fetch public key' => 'Nie udało się pobrać klucza publicznego',
    'Key length' => 'Długość klucza',
    'Fetched at' => 'Pobrano o',
    'Fetch failed' => 'Pobieranie nie powiodło się',
    'Unknown error' => 'Nieznany błąd',
    ':token_type token is required for fetching public key' => 'Do pobrania klucza publicznego wymagany jest token :token_type',
    
    // Payment Status Page
    'Payment Status' => 'Status płatności',
    'Payment Successful' => 'Płatność zakończona sukcesem',
    'Payment Failed' => 'Płatność nieudana',
    'Thank you for your payment! Your transaction has been successfully completed' => 'Dziękujemy za płatność! Twoja transakcja została pomyślnie zakończona',
    'Go to Invoice' => 'Przejdź do faktury',
    'Go to Invoices' => 'Przejdź do faktur',
    'Try Again' => 'Spróbuj ponownie',
    'Loading...' => 'Ładowanie...',
    'Checking payment status...' => 'Sprawdzanie statusu płatności...',
    'Missing invoice ID' => 'Brak identyfikatora faktury',
    'Payment completed successfully' => 'Płatność została pomyślnie zakończona',
    'Payment not found or not processed yet' => 'Nie znaleziono płatności lub nie została jeszcze przetworzona',
    'Redirecting to invoice' => 'Przekierowanie do faktury',
    'Payment status is being verified' => 'Trwa weryfikacja statusu płatności',
    'Payment status unknown or failed' => 'Status płatności nieznany lub nieudany',
    'System error occurred' => 'Wystąpił błąd systemowy',
    
    // Monobank Error Messages
    'Operation blocked by issuing bank' => 'Operacja zablokowana przez bank-wydawcę',
    'Card lost. Expenses limited' => 'Karta zgubiona. Wydatki ograniczone',
    'Card expenses limited' => 'Wydatki na karcie ograniczone',
    'Card expiration date expired' => 'Termin ważności karty wygasł',
    'Incorrect card number' => 'Nieprawidłowy numer karty',
    'Technical failure occurred' => 'Wystąpiła awaria techniczna',
    'Merchant point configuration error' => 'Błąd konfiguracji punktu handlowego',
    'Card type does not support such payments' => 'Ten typ karty nie obsługuje takich płatności',
    'Transaction not supported' => 'Transakcja nieobsługiwana',
    'Card expenses limited for purchases' => 'Wydatki na karcie ograniczone dla zakupów',
    'Insufficient funds on card' => 'Niewystarczające środki na karcie',
    'Card expense operation limit exceeded' => 'Przekroczono limit operacji wydatkowych karty',
    'Card internet limit exceeded' => 'Przekroczono limit internetowy karty',
    'PIN code limit exceeded' => 'Przekroczono limit prób wprowadzenia kodu PIN',
    'Operation rejected by payment system' => 'Operacja odrzucona przez system płatniczy',
    'Routing error' => 'Błąd trasowania',
    'Incorrect CVV code' => 'Nieprawidłowy kod CVV',
    'Incorrect CVV2 code' => 'Nieprawidłowy kod CVV2',
    'Transaction not allowed with such conditions' => 'Transakcja niedozwolona przy takich warunkach',
    'Card payment attempt limits exceeded' => 'Przekroczono limit prób płatności kartą',
    'Incorrect 3D Secure verification value' => 'Nieprawidłowa wartość weryfikacji 3D Secure',
    'Internal system error' => 'Wewnętrzny błąd systemu',
    'Full card details required for payment' => 'Do dokonania płatności wymagane są pełne dane karty',
    '3-D Secure verification failed' => 'Weryfikacja 3-D Secure nie powiodła się',
    'Transfer only possible to Ukrainian bank card' => 'Przelew możliwy tylko na kartę ukraińskiego banku',
    'Payment only possible with Mastercard or Visa' => 'Płatność możliwa tylko kartami Mastercard lub Visa',
    'Payment amount less than minimum allowed' => 'Kwota płatności mniejsza niż minimalna dozwolona',
    'Incorrect card expiration date' => 'Nieprawidłowa data ważności karty',
    'Customer information not found' => 'Nie znaleziono informacji o kliencie',
    'Minimum transfer amount' => 'Minimalna kwota przelewu',
    'Recipient name required' => 'Wymagane jest podanie imienia i nazwiska odbiorcy',
    'This top-up method only works with other bank cards' => 'Ta metoda doładowania działa tylko z kartami innych banków',
    'CVV code required' => 'Wymagany jest kod CVV',
    'Payment system limited transfers' => 'System płatniczy ograniczył przelewy',
    'Card blocked by risk management' => 'Karta zablokowana przez dział ryzyka',
    'Operation blocked by risk management' => 'Operacja zablokowana przez dział ryzyka',
    'This type of operations with hryvnia cards temporarily limited' => 'Ten typ operacji kartami w hrywnach jest tymczasowo ograniczony',
    '3-D Secure stage error' => 'Błąd na etapie 3-D Secure',
    'Check recipient name and surname' => 'Sprawdź imię i nazwisko odbiorcy',
    'Russian cards not supported' => 'Karty rosyjskie nie są obsługiwane',
    'Operation not allowed for eVidnovlennya program' => 'Operacja niedozwolona w ramach programu eVidnovlennya',
    'Operation rejected at 3DS step' => 'Operacja odrzucona na etapie 3DS',
    'Payment link expired' => 'Wygasł link do płatności',
    'Client cancelled payment' => 'Klient anulował płatność',
    '3-D Secure processing problems' => 'Problemy z przetwarzaniem 3-D Secure',
    'Payment acceptance limits exceeded' => 'Przekroczono limity przyjmowania płatności',
    
    'Support Information' => 'Informacje o wsparciu',
    'Supported Features' => 'Obsługiwane funkcje',
    'Card payments (Visa, Mastercard)' => 'Płatności kartami (Visa, Mastercard)',
    'Monobank app payments' => 'Płatności aplikacją Monobank',
    'Apple Pay / Google Pay' => 'Apple Pay / Google Pay',
    'QR code payments' => 'Płatności kodem QR',
    'Payment holds and cancellations' => 'Wstrzymania i anulowania płatności',
    'iFrame integration' => 'Integracja iFrame',
    'Supported Currency' => 'Obsługiwana waluta',
    'Documentation' => 'Dokumentacja',
    
    'Save Configuration' => 'Zapisz konfigurację',
    'Test Connection' => 'Testuj połączenie',
    'Testing...' => 'Testowanie...',
    
    // Client Area
    'Payment Details' => 'Szczegóły płatności',
    'Invoice' => 'Faktura',
    'Amount' => 'Kwota',
    'Client' => 'Klient',
    'Guest' => 'Gość',
    'Payment Methods' => 'Metody płatności',
    'Bank cards (Visa, Mastercard)' => 'Karty płatnicze (Visa, Mastercard)',
    'Monobank mobile app' => 'Aplikacja mobilna Monobank',
    'QR code payment' => 'Płatność kodem QR',
    
    'Loading payment form...' => 'Ładowanie formularza płatności...',
    'Loading secure payment form...' => 'Ładowanie bezpiecznego formularza płatności...',
    'Having trouble with the payment form?' => 'Masz problem z formularzem płatności?',
    'Open in new window' => 'Otwórz w nowym oknie',
    
    'You will be redirected to secure payment page in' => 'Zostaniesz przekierowany na bezpieczną stronę płatności za',
    'seconds' => 'sekund',
    'Proceed to Payment' => 'Przejdź do płatności',
    'You will be redirected to Monobank secure payment page' => 'Zostaniesz przekierowany na bezpieczną stronę płatności Monobank',
    
    'Your payment is protected by Monobank security systems' => 'Twoja płatność jest chroniona przez systemy bezpieczeństwa Monobank',
    'Transaction ID' => 'ID transakcji',
    
    'Need Help?' => 'Potrzebujesz pomocy?',
    'Payment Issues' => 'Problemy z płatnością',
    'Contact our support team' => 'Skontaktuj się z naszym działem wsparcia',
    'Monobank Support' => 'Wsparcie Monobank',
    
    // Error Messages
    'Payment Error' => 'Błąd płatności',
    'Unable to process payment' => 'Nie można przetworzyć płatności',
    'An error occurred while processing your payment. Please try again.' => 'Wystąpił błąd podczas przetwarzania płatności. Spróbuj ponownie.',
    'Error Code' => 'Kod błędu',
    'Try Again' => 'Spróbuj ponownie',
    'Go Back' => 'Wróć',
    'If the problem persists, please contact our support team or try using a different payment method.' => 'Jeśli problem nie ustępuje, skontaktuj się z działem wsparcia lub spróbuj innej metody płatności.',
    
    'Currency Not Supported' => 'Waluta nieobsługiwana',
    'Sorry, this currency is not supported' => 'Przepraszamy, ta waluta nie jest obsługiwana',
    'Monobank currently only supports UAH (Ukrainian Hryvnia) payments.' => 'Monobank obecnie obsługuje wyłącznie płatności w UAH (hrywna ukraińska).',
    'Your invoice currency is' => 'Waluta Twojej faktury to',
    'Ukrainian Hryvnia' => 'Hrywna ukraińska',
    'Choose Different Payment Method' => 'Wybierz inną metodę płatności',
    'Alternative Options' => 'Alternatywne opcje',
    'Contact support to change invoice currency' => 'Skontaktuj się ze wsparciem, aby zmienić walutę faktury',
    'Use a different payment method that supports your currency' => 'Użyj innej metody płatności, która obsługuje Twoją walutę',
    'Consider currency conversion services' => 'Rozważ skorzystanie z usług wymiany walut',
    
    // Validation Messages
    'Production token is required when sandbox mode is disabled' => 'Token produkcyjny jest wymagany, gdy tryb sandbox jest wyłączony',
    'Production token must be at least 10 characters' => 'Token produkcyjny musi mieć co najmniej 10 znaków',
    'Sandbox token is required when sandbox mode is enabled' => 'Token sandbox jest wymagany, gdy tryb sandbox jest włączony',
    'Sandbox token must be at least 10 characters' => 'Token sandbox musi mieć co najmniej 10 znaków',
    'Payment timeout must be at least 5 minutes' => 'Limit czasu płatności musi wynosić co najmniej 5 minut',
    'Payment timeout cannot exceed 24 hours' => 'Limit czasu płatności nie może przekraczać 24 godzin',
    'Payment type must be either debit or hold' => 'Typ płatności musi być debet lub hold',
    
    // API Messages
    'API token is required' => 'Wymagany jest token API',
    'Connection successful' => 'Połączenie udane',
    'Connection failed' => 'Połączenie nieudane',
    'Test failed' => 'Test nie powiódł się',
    'Module not configured' => 'Moduł nie skonfigurowany',
    'Failed to get transaction status' => 'Nie udało się pobrać statusu transakcji',
    'Invoice ID is required' => 'Wymagany jest identyfikator faktury',
    'Payment cancelled successfully' => 'Płatność została pomyślnie anulowana',
    'Failed to cancel payment' => 'Nie udało się anulować płatności',
    'Payment cancellation failed' => 'Anulowanie płatności nie powiodło się',
    'Failed to get merchant info' => 'Nie udało się pobrać informacji o sprzedawcy',
    'Payment for invoice' => 'Płatność za fakturę',
    'Payment for services' => 'Płatność za usługi',
    'Failed to create payment' => 'Nie udało się utworzyć płatności',
    'System error occurred' => 'Wystąpił błąd systemowy',
    
    // Token Management
    'Configured' => 'Skonfigurowano',
    'Leave empty to keep current token' => 'Pozostaw puste, aby zachować obecny token',
    'Token is currently configured' => 'Token jest obecnie skonfigurowany',
    
    // Test Connection
    'Payment gateway not found' => 'Nie znaleziono bramki płatniczej',
    ':token_type token is required for testing' => 'Do testów wymagany jest token :token_type',
    'Merchant' => 'Sprzedawca',
    'Merchant ID' => 'ID sprzedawcy',
    'API Mode' => 'Tryb API',
    'Endpoint' => 'Endpoint',
    'Error Code' => 'Kod błędu',
    'Please check your API token and network connection' => 'Sprawdź token API i połączenie sieciowe',
    'Check browser console for details' => 'Sprawdź konsolę przeglądarki, aby uzyskać szczegóły',
    'Connected via API route' => 'Połączono przez trasę API',
];

