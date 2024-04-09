document.addEventListener('DOMContentLoaded', function () {

    const uslugaBlocks = document.querySelectorAll('.usluga-block');
    const summaryDiv = document.querySelector('.podsumowanie');
    const totalSpan = document.querySelector('.suma_miesiecznie');
    const activationTotalSpan = document.querySelector('.suma_aktywacyjna');
    let total = 0;
    let activationTotal = 0;
    let lastChecked = {};
    let selectedPackages = {};
    let packageServicesDetails = {};


    const calculateTotalFromSummary = () => {
        let newTotal = 0;
        let newActivationTotal = 0;

        // Sumowanie cen miesięcznych
        document.querySelectorAll('.podsumowanie-cena-miesieczna').forEach(priceElement => {
            const price = parseFloat(priceElement.textContent) || 0;
            newTotal += price;
        });

        // Sumowanie cen aktywacyjnych
        document.querySelectorAll('.podsumowanie-cena-aktywacyjna').forEach(activationPriceElement => {
            const activationPrice = parseFloat(activationPriceElement.textContent) || 0;
            newActivationTotal += activationPrice;
        });

        total = newTotal;
        activationTotal = newActivationTotal;
        updateDisplay(); // Aktualizacja wyświetlania sum
    };



    // Funkcja do pobrania danych o pakiecie
    const fetchPackageData = async () => {
        try {
            const response = await fetch('https://fastarswiatlowod.pl/wp-json/wp/v2/pakiet?per_page=99');
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const packages = await response.json();
            return packages.reduce((acc, pakiet) => {
                acc[pakiet.slug] = {
                    title: pakiet.name,
                    cenaMiesieczna: pakiet.acf.cena_miesieczna_pakietu,
                    cenaAktywacji: pakiet.acf.cena_aktywacji_pakietu,
                    cenaMiesieczna2: pakiet.acf.cena_miesieczna_pakietu_2,
                    cenaAktywacji2: pakiet.acf.cena_aktywacji_pakietu_2
                };
                return acc;
            }, {});
        } catch (error) {
            console.error('Could not fetch packages:', error);
        }
    };

    // Zmienna do przechowywania danych pakietów
    let packagesData = {};

    (async () => {
        packagesData = await fetchPackageData();
    })();


    const checkCompletePackages = () => {
        // Wyczyść aktualny stan pakietów
        selectedPackages = {};

        // Zbierz wszystkie wybrane usługi i ich pakiety
        document.querySelectorAll('.usluga-block input:checked').forEach(input => {
            const pakietData = input.closest('.usluga-block').dataset.pakiet.split(',');
            pakietData.forEach(pakiet => {
                if (!selectedPackages[pakiet]) {
                    selectedPackages[pakiet] = [];
                }
                selectedPackages[pakiet].push(input);
            });
        });

        // Sprawdź, czy którykolwiek z pakietów jest kompletny
        Object.keys(selectedPackages).forEach(pakiet => {
            const services = document.querySelectorAll(`.usluga-block[data-pakiet*="${pakiet}"]`);
            const selectedServices = selectedPackages[pakiet];
            const packageSummaryId = 'summary-package-' + pakiet;

            // Jeśli wszystkie usługi w pakiecie są zaznaczone, pakiet jest kompletny
            if (selectedServices.length === services.length) {
                // Usuń indywidualne usługi z podsumowania i dodaj pakiet
                packageServicesDetails[pakiet] = selectedServices.map(input => {
                    const block = input.closest('.usluga-block');
                    const monthlyPrice = parseFloat(block.dataset.cenaUslugi) || 0;
                    const activationPrice = parseFloat(block.dataset.cenaAktywacji) || 0;
                    return {
                        inputType: block.dataset.inputType,
                        itemId: input.id,
                        Itemname: input.name,
                        type: block.dataset.rodzaj,
                        title: block.dataset.tytul,
                        cenaMiesieczna: monthlyPrice,
                        cenaAktywacji: activationPrice,
                    };
                });

                if (packagesData && packagesData[pakiet]) {
                    const packageInfo = packagesData[pakiet];
                    selectedServices.forEach(input => removeSummaryItem('summary-item-' + input.id));
                    selectedServices.forEach(input => removeSummaryItem('checkbox-item-' + input.id));
                    selectedServices.forEach(input => {

                        const block = input.closest('.usluga-block');
                        const podsumowanieId = 'summary-taxonomy-' + block.dataset.rodzaj;
                        const podsumowanieElement = document.getElementById(podsumowanieId);
                        if (podsumowanieElement) {
                            const checkedCheckboxes = podsumowanieElement.querySelectorAll('.checkbox-items-list');

                            // Sprawdzenie czy jakikolwiek z elementów .checkbox-items-list ma inne elementy HTML (dzieci)
                            let hasChildren = Array.from(checkedCheckboxes).some(checkbox => checkbox.children.length > 0);

                            if (!hasChildren) {
                                removeSummaryItem(podsumowanieId);
                            }
                        }
                    });

                    if (zmianacenpakietow()) {
                        addSummaryItem('Pakiet', packageInfo.title, packageInfo.cenaMiesieczna2, packageInfo.cenaAktywacji2, null, packageSummaryId, 'package', null, packageServicesDetails[pakiet]);
                    } else {
                        addSummaryItem('Pakiet', packageInfo.title, packageInfo.cenaMiesieczna, packageInfo.cenaAktywacji, null, packageSummaryId, 'package', null, packageServicesDetails[pakiet]);
                    }

                } else {
                    console.error('No data for package:', pakiet);
                }
            } else {
                // Usuń informację o pakiecie z podsumowania, jeśli nie jest kompletny
                removeSummaryItem(packageSummaryId, true);
            }
        });

        // Dodaj do podsumowania tylko te usługi, które nie należą do żadnego kompletnego pakietu
        document.querySelectorAll('.usluga-block input:checked').forEach(input => {
            const block = input.closest('.usluga-block');
            const inputType = block.dataset.inputType;
            const pakietData = block.dataset.pakiet.split(',');

            let isPartOfCompletePackage = pakietData.some(pakiet =>
                selectedPackages[pakiet] &&
                selectedPackages[pakiet].length === document.querySelectorAll(`.usluga-block[data-pakiet*="${pakiet}"]`).length
            );

            if (inputType === 'radio') {

                // Jeśli usługa nie jest częścią kompletnego pakietu, dodaj ją do podsumowania
                if (!isPartOfCompletePackage) {
                    const title = block.dataset.tytul;
                    const type = block.dataset.rodzaj;
                    const price = block.dataset.cenaUslugi || 0;
                    const activationPrice = block.dataset.cenaAktywacji || 0;
                    const itemId = 'summary-item-' + input.id;
                    addSummaryItem(type, title, price, activationPrice, input.name, itemId, input.type);
                }
            }
        });

    };


    const updateDisplay = () => {
        totalSpan.innerHTML = `<strong>${total.toFixed(2)}zł</strong>/miesięcznie`;
        activationTotalSpan.innerHTML = `aktywacja jednorazowa <strong>${activationTotal.toFixed(2)}zł</strong>`;
    };

    const removeSummaryItem = (id, log = false) => {
        const item = document.getElementById(id);
        if (item) {
            // Jeśli logowanie jest aktywne, zapisz informacje przed usunięciem
            if (log) {
                const packageId = id.replace('summary-package-', '');
                const packageServices = packageServicesDetails[packageId];
                if (packageServices) {
                    // Iteruj po usługach w pakiecie
                    packageServices.forEach(service => {
                        // Sprawdź, czy typ inputu to 'checkbox'
                        if (service.inputType === 'checkbox') {
                            const title = service.title;
                            const type = service.type;
                            const price = service.cenaMiesieczna || 0;
                            const activationPrice = service.cenaAktywacji || 0;
                            const typeId = 'summary-taxonomy-' + service.type;



                            // Znajdź element input na podstawie jego ID, które jest przechowywane w service.itemId
                            const inputElement = document.getElementById(service.itemId);

                            // Sprawdź, czy input jest zaznaczony
                            if (inputElement && inputElement.checked) {
                                // Jeśli tak, wywołaj funkcję addSummaryItem z odpowiednimi parametrami
                                addSummaryItem(type, title, price, activationPrice, service.Itemname, typeId, service.inputType, service.itemId);
                            }

                        }
                    });
                }
            }
            item.remove();
        }
    };
        


    const addSummaryItem = (type, title, price, activationPrice, inputName, itemId, inputType, checkboxId, servicesDetails) => {
        let item = document.getElementById(itemId);
        if (inputType === "checkbox") {
            if (!item) {
                item = document.createElement('div');
                item.className = 'podsumowanie-element podsumowanie-element-checkbox';
                item.id = itemId;
                item.setAttribute('data-related-input', inputName);
                item.innerHTML = `<div><strong class="podsumowanie-title"><span>+ </span>${type}</strong></div>`;
                const itemsList = document.createElement('div');
                itemsList.className = 'checkbox-items-list';
                item.appendChild(itemsList);
                summaryDiv.appendChild(item);
            }

            const itemsList = item.querySelector('.checkbox-items-list');
            const checkboxItem = document.createElement('div');
            checkboxItem.id = 'checkbox-item-' + checkboxId;  // Dodaj identyfikator dla konkretnego elementu listy
            checkboxItem.innerHTML = `
                ${title} - 
                ${price ? `<strong>cena miesięczna: </strong><span class="podsumowanie-cena-miesieczna">${price}</span> zł` : ''}
                ${activationPrice ? `, cena aktywacyjna: <strong><span class="podsumowanie-cena-aktywacyjna">${activationPrice}<span> zł</strong>` : ''}
            `;
            itemsList.appendChild(checkboxItem);
            const paragraph = document.createElement('p');
            paragraph.style.margin = "0px";
            item.appendChild(paragraph);
        } else if (inputType === "radio") {
            if (!item) {
                item = document.createElement('div');
                item.className = 'podsumowanie-element podsumowanie-element-radio';
                item.id = itemId;
                item.setAttribute('data-related-input', inputName);
                item.innerHTML = `
                    <div><strong class="podsumowanie-title"><span>+ </span>${type} - ${title}</strong></div>
                    <div>
                        <div>
                            ${price ? `<div><strong>cena miesięczna: </strong><span class="podsumowanie-cena-miesieczna">${price}</span> zł</div>` : ''}
                            ${activationPrice ? `<div><strong>cena aktywacyjna: </strong><span class="podsumowanie-cena-aktywacyjna">${activationPrice} </span>zł</div>` : ''}
                        </div>
                    </div>
                    <p style="margin: 0px"></p>
                `;
                summaryDiv.appendChild(item);
            }
        } else {
            if (!item) {
                item = document.createElement('div');
                item.className = 'podsumowanie-element podsumowanie-element-pakiet';
                item.id = itemId;
                item.setAttribute('data-related-input', inputName);
                let servicesListHTML = "";
                let totalMonthlyPrice = 0; // Suma przekreślonych cen miesięcznych
                let totalActivationPrice = 0; // Suma przekreślonych cen aktywacyjnych

                if (servicesDetails) {
                    servicesDetails.forEach(service => {
                        totalMonthlyPrice += service.cenaMiesieczna || 0;
                        totalActivationPrice += service.cenaAktywacji || 0;
                        servicesListHTML += `<div>
                                                <strong>${service.type}</strong>: ${service.title}
                                            </div>`;
                    });
                }

                // Teraz dodaj ceny pakietu, które nie będą przekreślone
                item.innerHTML = `<div class="gradient"></div>
                    <div class="pakiety-title-container">
                    <div><strong class="podsumowanie-title"><span>+ </span>${type} - ${title}</strong></div>
                    <a class="wiecej_pakietow" href="https://fastarswiatlowod.pl/wp-content/uploads/2023/11/pakiety_fastar.pdf">Więcej pakietów</a>
                    </div>
                    <div>
                        ${servicesListHTML}
                        <div>
                            <div><strong>Cena miesięczna: </strong><span class="podsumowanie-cena-miesieczna">${price}</span> zł <s>${totalMonthlyPrice.toFixed(2)} zł</s></div>
                            <div><strong>Cena aktywacyjna: </strong><span class="podsumowanie-cena-aktywacyjna">${activationPrice}</span> zł <s>${totalActivationPrice.toFixed(2)} zł</s></div>
                        </div>
                    </div>
                    <div class="gradient"></div>
                    <p style="margin: 0px"></p>
                `;
                summaryDiv.appendChild(item);
            }
        }
    };


    let previousInputsWithAttributeCount = 0;

    function checkSelectedInputs() {
        // Znajdź wszystkie zaznaczone inputy w elemencie z klasą 'konfigurator-column'
        const selectedInputs = document.querySelectorAll('.konfigurator-column input:checked');

        // Filtruj te inputy, które mają odpowiadający im element z atrybutem 'data-ustaw-ceny-2="1"'
        const inputsWithAttribute = Array.from(selectedInputs).filter(input => {
            const parentBlock = input.closest('.usluga-block');
            return parentBlock && parentBlock.getAttribute('data-ustaw-ceny-2') === '1';
        });

        // Znajdź wszystkie bloki usług
        const serviceBlocks = document.querySelectorAll('.konfigurator-column .usluga-block');

        // Dla każdego bloku usługi, zaktualizuj wyświetlane ceny i zamień wartości atrybutów
        serviceBlocks.forEach(block => {
            // Pobierz wartości z atrybutów
            const cenaMiesieczna = block.getAttribute('data-cena-uslugi-3');
            const cenaAktywacyjna = block.getAttribute('data-cena-aktywacji-3');
            const cenaMiesieczna2 = block.getAttribute('data-cena-uslugi-2');
            const cenaAktywacyjna2 = block.getAttribute('data-cena-aktywacji-2');

            // Zaktualizuj wyświetlane ceny
            const cenaMiesiecznaElement = block.querySelector('.cena-miesiczna-uslugi');
            const cenaAktywacyjnaElement = block.querySelector('.cena-aktywacyjna-uslugi');

            if (inputsWithAttribute.length > 0) {


                if (cenaMiesiecznaElement) cenaMiesiecznaElement.textContent = cenaMiesieczna2 + ' zł';
                if (cenaAktywacyjnaElement) cenaAktywacyjnaElement.textContent = cenaAktywacyjna2 + ' zł';

                block.setAttribute('data-cena-uslugi', cenaMiesieczna2);
                block.setAttribute('data-cena-aktywacji', cenaAktywacyjna2);
                // Zamień wartości atrybutów

            } else {
                // Użyj standardowych cen jeśli warunek nie jest spełniony


                if (cenaMiesiecznaElement) cenaMiesiecznaElement.textContent = cenaMiesieczna + ' zł';
                if (cenaAktywacyjnaElement) cenaAktywacyjnaElement.textContent = cenaAktywacyjna + ' zł';

                block.setAttribute('data-cena-uslugi', cenaMiesieczna);
                block.setAttribute('data-cena-aktywacji', cenaAktywacyjna);
            }
        });


        // Sprawdź, czy liczba zaznaczonych inputów spełniających kryteria zmieniła się z 0 na większą lub na odwrót
        if ((previousInputsWithAttributeCount === 0 && inputsWithAttribute.length > 0) ||
            (previousInputsWithAttributeCount > 0 && inputsWithAttribute.length === 0)) {
            clearSummary()

            // I analogicznie przy wywołaniu dla zaznaczonych inputów
            uslugaBlocks.forEach(block => {
                const input = block.querySelector('input');
                const title = block.dataset.tytul;
                const type = block.dataset.rodzaj;
                const price = block.dataset.cenaUslugi || 0;
                const activationPrice = block.dataset.cenaAktywacji || 0;
                const itemId = 'summary-taxonomy-' + type;
                const checkboxId = input.id;  // Uzyskaj identyfikator dla konkretnego checkboxa
                const inputType = block.dataset.inputType;
                if (input.checked && inputType === 'checkbox') {
                    console.log('Zmiana liczby zaznaczonych inputów z/na 0.');
                    addSummaryItem(type, title, price, activationPrice, input.name, itemId, inputType, checkboxId);
                }
            });

        }

        // Zaktualizuj przechowywaną liczbę zaznaczonych inputów
        previousInputsWithAttributeCount = inputsWithAttribute.length;


    }

    function clearSummary() {
        const summaryDiv = document.querySelector('.podsumowanie');
        if (summaryDiv) {
            summaryDiv.innerHTML = ''; // To usunie całą zawartość wewnętrzną elementu .podsumowanie
        }
    }

    function zmianacenpakietow() {
        // Znajdź wszystkie zaznaczone inputy w elemencie z klasą 'konfigurator-column'
        const selectedInputs = document.querySelectorAll('.konfigurator-column input:checked');

        // Filtruj te inputy, które mają odpowiadający im element z atrybutem 'data-ustaw-ceny-2="1"'
        const inputsWithAttribute = Array.from(selectedInputs).filter(input => {
            const parentBlock = input.closest('.usluga-block');
            return parentBlock && parentBlock.getAttribute('data-ustaw-ceny-2') === '1';
        });

        // Jeśli istnieje choć jeden input spełniający warunek, zwróć true
        if (inputsWithAttribute.length > 0) {
            return true;
        } else {
            return false;
        }
    }




    function handleUslugaClick(input, block) {
        const title = block.dataset.tytul;
        const type = block.dataset.rodzaj;
        const price = block.dataset.cenaUslugi || 0;
        const activationPrice = block.dataset.cenaAktywacji || 0;
        const itemId = 'summary-item-' + input.id;
        const inputType = block.dataset.inputType;
    
        if (inputType === 'radio') {
            if (input === lastChecked[input.name]) {
                // Jeśli rodzaj usługi to "Typ przyłącza", zablokuj odznaczenie
                if (type === "Typ przyłącza") {
                    input.checked = true; // Zostaw zaznaczenie aktywne
                } else {
                    input.checked = false;
                    lastChecked[input.name] = null;
                    removeSummaryItem(itemId);
                }
            } else {
                if (lastChecked[input.name] && type !== "Typ przyłącza") {
                    // Usuń poprzedni wybrany element, jeśli nie jest "Typ przyłącza"
                    removeSummaryItem('summary-item-' + lastChecked[input.name].id);
                }
    
                if (input.checked) {
                    addSummaryItem(type, title, price, activationPrice, input.name, itemId, inputType);
                } else {
                    removeSummaryItem(itemId);
                }
    
                lastChecked[input.name] = input;
            }
        } else if (inputType === 'checkbox') {
            const itemId = 'summary-taxonomy-' + type;
            const checkboxId = input.id;  // Uzyskaj identyfikator dla konkretnego checkboxa
            if (input.checked) {
                addSummaryItem(type, title, price, activationPrice, input.name, itemId, inputType, checkboxId);
            } else {
                removeSummaryItem('checkbox-item-' + checkboxId);  // Usuń tylko konkretny element z listy
                const checkedCheckboxes = block.closest('.usluga-block-container').querySelectorAll('input[type="checkbox"]:checked');
                if (!checkedCheckboxes.length) {
                    removeSummaryItem(itemId);
                }
            }
        }

        if (Math.abs(total) < 0.0001) total = 0;
        if (Math.abs(activationTotal) < 0.0001) activationTotal = 0;

        checkSelectedInputs();
        checkCompletePackages(); // Sprawdź kompletność pakietów po aktualizacji podsumowania
        calculateTotalFromSummary();
    }





    // W miejscu wywołania funkcji przekazujemy zarówno 'input', jak i 'block'
    uslugaBlocks.forEach(block => {
        const input = block.querySelector('input');
        input.addEventListener('click', function () {
            handleUslugaClick(this, block);  // Przekazujemy 'block' jako argument
        });
    });

    // I analogicznie przy wywołaniu dla zaznaczonych inputów
    uslugaBlocks.forEach(block => {
        const input = block.querySelector('input');
        if (input.checked) {
            handleUslugaClick(input, block);  // Przekazujemy 'block' jako argument
        }
    });





});

jQuery(document).ready(function ($) {
    $('form').on('submit', function (e) {
        var podsumowanieContent = $('.podsumowanie').clone(); // Klonuj element, aby nie modyfikować oryginału
        podsumowanieContent.find('.wiecej_pakietow').remove(); // Usuń element 'Więcej pakietów' z kopii

        // Tworzy ukryte pole, jeśli nie istnieje
        if ($('input[name="podsumowanie"]').length === 0) {
            $(this).append('<input type="hidden" name="podsumowanie">');
        }

        // Ustawia wartość ukrytego pola na HTML bez linku 'Więcej pakietów'
        $('input[name="podsumowanie"]').val(podsumowanieContent.html());
    });
});


jQuery(document).ready(function ($) {
    $('form').on('submit', function (e) {
        var sumaContent = $('.suma').html();
        // Tworzy ukryte pole, jeśli nie istnieje
        if ($('input[name="suma"]').length === 0) {
            $(this).append('<input type="hidden" name="suma">');
        }
        // Ustawia wartość ukrytego pola
        $('input[name="suma"]').val(sumaContent);
    });
});
