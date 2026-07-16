export default {
    brand: {
        tagline: 'Ruimte om te denken',
    },
    common: {
        cancel: 'Annuleren',
        save: 'Opslaan',
        saving: 'Opslaan…',
    },
    sidebar: {
        search: 'Zoeken',
        boards: 'Boards',
        newBoard: 'Nieuw board',
        allBoards: 'Alle boards',
        admin: 'Beheer',
        adminOverview: 'Overzicht',
        adminUsers: 'Gebruikers',
        adminBoards: 'Alle boards',
        adminAudit: 'Auditlog',
    },
    admin: {
        pagination: {
            showing: '{from}–{to} van {total}',
            page: 'Pagina {current} van {last}',
            previous: 'Vorige',
            next: 'Volgende',
        },
        users: {
            title: 'Gebruikers',
            subtitle: '{count} geregistreerde gebruikers',
            searchPlaceholder: 'Zoek op naam of e-mailadres…',
            columnUser: 'Gebruiker',
            columnRole: 'Rol',
            columnStatus: 'Status',
            columnBoards: 'Boards',
            roleUser: 'Gebruiker',
            roleAdmin: 'Beheerder',
            active: 'Actief',
            suspended: 'Geschorst',
            suspend: 'Schorsen',
            unsuspend: 'Opheffen',
            you: 'Jij',
            empty: 'Geen gebruikers gevonden.',
            deleteTitle: '{name} verwijderen?',
            deleteDescription:
                'Dit verwijdert het account definitief en kan niet ongedaan worden gemaakt.',
            deleteBoardsWarning:
                'Hiermee worden ook {count} board(s) van deze gebruiker definitief verwijderd, inclusief boards die met anderen zijn gedeeld.',
            deleteConfirm: 'Account verwijderen',
            roleError: 'Kon de rol niet wijzigen.',
            suspendError: 'Kon de schorsing niet bijwerken.',
            deleteError: 'Kon de gebruiker niet verwijderen.',
        },
        boards: {
            title: 'Alle boards',
            subtitle: '{count} boards over alle accounts',
            searchPlaceholder: 'Zoek boards…',
            columnBoard: 'Board',
            columnOwner: 'Eigenaar',
            columnTasks: 'Taken',
            columnNotes: 'Notities',
            columnCollaborators: 'Gedeeld met',
            empty: 'Geen boards gevonden.',
        },
        boardDetail: {
            ownedBy: 'Eigendom van {name} ({email})',
            readOnlyNotice:
                'Alleen-lezen weergave. Het openen van dit board wordt vastgelegd in het auditlog.',
            tasks: 'Taken ({count})',
            notes: 'Notities ({count})',
            collaborators: 'Deelnemers ({count})',
            noTasks: 'Geen taken op dit board.',
            noNotes: 'Geen notities op dit board.',
            noCollaborators: 'Dit board is niet gedeeld.',
        },
        metrics: {
            title: 'Overzicht',
            subtitle: 'Activiteit binnen de hele applicatie.',
            tiles: {
                users: 'Gebruikers',
                boards: 'Boards',
                openTasks: 'Open taken',
                notes: 'Notities',
                admins: 'Beheerders',
                suspended: 'Geschorst',
                tasks: 'Taken totaal',
            },
            signups: 'Aanmeldingen, laatste 30 dagen',
            boardsCreated: 'Nieuwe boards, laatste 30 dagen',
            recentActivity: 'Recente activiteit',
            noActivity: 'Er is nog niets vastgelegd.',
        },
        audit: {
            title: 'Auditlog',
            subtitle: '{count} vastgelegde gebeurtenissen',
            searchPlaceholder: 'Zoek op actor of doel…',
            allActions: 'Alle acties',
            columnWhen: 'Wanneer',
            columnActor: 'Actor',
            columnAction: 'Actie',
            columnTarget: 'Doel',
            columnIp: 'IP',
            empty: 'Geen gebeurtenissen gevonden.',
        },
    },
    userMenu: {
        settings: 'Instellingen',
        logout: 'Uitloggen',
    },
    settings: {
        title: 'Instellingen',
        description: 'Beheer je profiel- en accountinstellingen',
        nav: {
            profile: 'Profiel',
            security: 'Beveiliging',
            notifications: 'Notificaties',
            appearance: 'Weergave',
        },
        notifications: {
            headTitle: 'Notificatie-instellingen',
            title: 'E-mailnotificaties',
            description: 'Kies welke e-mails Anchor je stuurt',
            saveError: 'Kon die instelling niet opslaan.',
            types: {
                board_shared: {
                    label: 'Een board wordt met mij gedeeld',
                    description:
                        'Wanneer iemand je toegang geeft tot een van zijn boards.',
                },
                board_role_changed: {
                    label: 'Mijn rol wijzigt',
                    description:
                        'Wanneer je toegang op een gedeeld board verandert tussen bewerker en kijker.',
                },
                board_access_revoked: {
                    label: 'Mijn toegang wordt ingetrokken',
                    description:
                        'Wanneer iemand je toegang tot een gedeeld board intrekt.',
                },
            },
        },
        profile: {
            headTitle: 'Profielinstellingen',
            title: 'Profiel',
            description: 'Werk je naam en e-mailadres bij',
            name: 'Naam',
            namePlaceholder: 'Volledige naam',
            email: 'E-mailadres',
            emailPlaceholder: 'E-mailadres',
            save: 'Opslaan',
            unverified: 'Je e-mailadres is niet geverifieerd.',
            resend: 'Klik hier om de verificatiemail opnieuw te versturen.',
            verificationSent:
                'Er is een nieuwe verificatielink naar je e-mailadres gestuurd.',
        },
        photo: {
            title: 'Foto',
            description: 'Upload een profielfoto.',
            upload: 'Foto uploaden',
            uploading: 'Uploaden…',
            remove: 'Verwijderen',
        },
        appearance: {
            headTitle: 'Weergave-instellingen',
            title: 'Weergave-instellingen',
            description: 'Pas de weergave-instellingen van je account aan',
            light: 'Licht',
            dark: 'Donker',
            system: 'Systeem',
            languageTitle: 'Taal',
            languageDescription:
                'Kies de taal die in de hele app wordt gebruikt',
            languageLabel: 'Taal',
        },
        security: {
            headTitle: 'Beveiligingsinstellingen',
            title: 'Wachtwoord wijzigen',
            description:
                'Gebruik een lang, willekeurig wachtwoord om je account veilig te houden',
            currentPassword: 'Huidig wachtwoord',
            newPassword: 'Nieuw wachtwoord',
            confirmPassword: 'Bevestig wachtwoord',
            save: 'Opslaan',
        },
        deleteAccount: {
            title: 'Account verwijderen',
            description: 'Verwijder je account en alle bijbehorende gegevens',
            warning: 'Let op',
            warningBody:
                'Ga voorzichtig te werk, dit kan niet ongedaan worden gemaakt.',
            button: 'Account verwijderen',
            dialogTitle: 'Weet je zeker dat je je account wilt verwijderen?',
            dialogDescription:
                'Zodra je account is verwijderd, worden ook alle bijbehorende gegevens definitief verwijderd. Voer je wachtwoord in om te bevestigen dat je je account definitief wilt verwijderen.',
            password: 'Wachtwoord',
            confirm: 'Account verwijderen',
        },
        twoFactor: {
            title: 'Tweestapsverificatie',
            description: 'Beheer je instellingen voor tweestapsverificatie',
            disabledBody:
                'Als je tweestapsverificatie inschakelt, wordt bij het inloggen om een beveiligde pincode gevraagd. Deze pincode haal je op uit een TOTP-app op je telefoon.',
            enabledBody:
                'Bij het inloggen wordt om een beveiligde, willekeurige pincode gevraagd. Die haal je op uit de TOTP-app op je telefoon.',
            continueSetup: 'Instellen hervatten',
            enable: '2FA inschakelen',
            disable: '2FA uitschakelen',
            modal: {
                enabledTitle: 'Tweestapsverificatie ingeschakeld',
                enabledDescription:
                    'Tweestapsverificatie is nu ingeschakeld. Scan de QR-code of voer de installatiesleutel in je authenticator-app in.',
                verifyTitle: 'Verificatiecode controleren',
                verifyDescription:
                    'Voer de 6-cijferige code uit je authenticator-app in',
                setupTitle: 'Tweestapsverificatie inschakelen',
                setupDescription:
                    'Scan de QR-code of voer de installatiesleutel in je authenticator-app in om tweestapsverificatie af te ronden',
                close: 'Sluiten',
                continue: 'Doorgaan',
                manualDivider: 'of voer de code handmatig in',
                back: 'Terug',
                confirm: 'Bevestigen',
            },
            recovery: {
                title: '2FA-herstelcodes',
                description:
                    'Met herstelcodes krijg je weer toegang als je je 2FA-apparaat kwijtraakt. Bewaar ze in een veilige wachtwoordmanager.',
                view: 'Herstelcodes tonen',
                hide: 'Herstelcodes verbergen',
                regenerate: 'Codes opnieuw genereren',
                note: 'Elke herstelcode kan één keer worden gebruikt en vervalt daarna. Heb je er meer nodig, klik dan hierboven op {action}.',
            },
        },
        passkeys: {
            title: 'Passkeys',
            description: 'Beheer je passkeys om zonder wachtwoord in te loggen',
            empty: 'Nog geen passkeys',
            emptyBody: 'Voeg een passkey toe om zonder wachtwoord in te loggen',
            added: 'Toegevoegd {when}',
            lastUsed: 'Laatst gebruikt {when}',
            remove: 'Verwijderen',
            removeTitle: 'Passkey verwijderen',
            removeDescription:
                'Weet je zeker dat je de passkey "{name}" wilt verwijderen? Je kunt er daarna niet meer mee inloggen.',
            removing: 'Verwijderen…',
            unsupported: 'Passkeys worden niet ondersteund in deze browser.',
            add: 'Passkey toevoegen',
            nameLabel: 'Naam van passkey',
            namePlaceholder: 'bijv. MacBook Pro, iPhone',
            nameHint: 'Met een naam herken je deze passkey later terug.',
            register: 'Passkey registreren',
            registering: 'Registreren…',
        },
    },
    boardsIndex: {
        title: 'Boards',
        subtitle: 'Boards die je bezit of waartoe je toegang hebt gekregen.',
        newBoard: 'Nieuw board',
        emptyTitle: 'Nog geen boards',
        emptySubtitle:
            'Maak je eerste board om taken en notities bij te houden.',
        open: '{count} open',
        boardOptions: 'Boardopties',
        rename: 'Hernoemen',
        share: 'Delen',
        delete: 'Verwijderen',
        deleteConfirmTitle: 'Board "{name}" verwijderen?',
        deleteConfirmBody:
            'Dit verwijdert het board en alle taken en notities definitief. Dit kan niet ongedaan worden gemaakt.',
        deleteBoard: 'Board verwijderen',
        deleteError: 'Kon het board niet verwijderen. Probeer het opnieuw.',
    },
    createBoard: {
        title: 'Nieuw board',
        description: 'Geef deze verzameling een korte, duidelijke naam.',
        namePlaceholder: 'Productlancering',
        submit: 'Board aanmaken',
        error: 'Kon het board niet aanmaken. Probeer het opnieuw.',
    },
    renameBoard: {
        title: 'Board hernoemen',
        error: 'Kon het board niet hernoemen. Probeer het opnieuw.',
    },
    shareBoard: {
        title: 'Deel "{name}"',
        description:
            'Nodig mensen uit om dit board te bekijken of te bewerken.',
        emailPlaceholder: "persoon{'@'}voorbeeld.nl",
        invite: 'Uitnodigen',
        editor: 'Bewerker',
        viewer: 'Kijker',
        members: 'Mensen met toegang',
        removeAccess: 'Toegang intrekken',
        noOneYet: 'Nog niemand anders heeft toegang.',
        pending: 'Uitgenodigd',
        pendingHint: 'Krijgt toegang zodra diegene zich aanmeldt',
        revokeInvite: 'Uitnodiging intrekken',
        invitationSent: 'Uitnodiging verstuurd naar {email}.',
        inviteError:
            'Kon het board niet delen. Controleer het e-mailadres en probeer het opnieuw.',
        roleError: 'Kon de toegang niet aanpassen. Probeer het opnieuw.',
        removeError: 'Kon de toegang niet intrekken. Probeer het opnieuw.',
    },
    board: {
        viewOnly: 'Je hebt alleen-lezen toegang tot dit board.',
        openCompleted: '{open} open · {completed} voltooid',
        boardMenu: 'Boardmenu',
        renameBoard: 'Board hernoemen',
        shareBoard: 'Board delen',
        manageLabels: 'Labels beheren',
        deleteBoard: 'Board verwijderen',
        deleteConfirmTitle: 'Board "{name}" verwijderen?',
        deleteConfirmBody:
            'Dit verwijdert het board en alle taken en notities definitief. Dit kan niet ongedaan worden gemaakt.',
        tabs: { tasks: 'Taken', notes: 'Notities' },
        addTaskPlaceholder: 'Taak toevoegen…',
        add: 'Toevoegen',
        priority: {
            none: 'Geen prioriteit',
            low: 'Laag',
            medium: 'Gemiddeld',
            high: 'Hoog',
        },
        filters: { all: 'Alle', open: 'Open', done: 'Klaar' },
        filterPlaceholder: 'Taken filteren',
        emptyNothingHere: 'Niks te zien hier',
        emptyTryAnotherSearch: 'Probeer een andere zoekopdracht.',
        emptyAddTaskAbove: 'Voeg hierboven een taak toe.',
        deleteTask: 'Taak verwijderen',
        deleteTaskConfirmTitle: '"{name}" verwijderen?',
        deleteTaskConfirmBody:
            'Dit verwijdert de taak definitief. Dit kan niet ongedaan worden gemaakt.',
        labelsButton: 'Labels',
        noLabelsYet: 'Nog geen labels',
        newLabelPlaceholder: 'Nieuw label',
        addLabel: 'Toevoegen',
        dueDate: 'Deadline',
        clearDueDate: 'Deadline wissen',
        overdue: 'Te laat',
        notesCount: '0 notities | 1 notitie | {count} notities',
        newNote: 'Nieuwe notitie',
        noNotesYetTitle: 'Nog geen notities',
        noNotesYetSubtitle: 'Schrijf op wat het bewaren waard is.',
        untitled: 'Naamloos',
        deleteNote: 'Notitie verwijderen',
        newPage: 'Nieuwe pagina',
        addSubpage: 'Subpagina toevoegen',
        deletePage: 'Pagina verwijderen',
        toastAddTaskError: 'Kon de taak niet toevoegen. Probeer het opnieuw.',
        toastUpdateTaskError:
            'Kon de taak niet bijwerken. Probeer het opnieuw.',
        toastPriorityError:
            'Kon de prioriteit niet bijwerken. Probeer het opnieuw.',
        toastDeleteTaskError:
            'Kon de taak niet verwijderen. Probeer het opnieuw.',
        toastCreateNoteError:
            'Kon de notitie niet aanmaken. Probeer het opnieuw.',
        toastSaveNoteError: 'Kon de notitie niet opslaan. Probeer het opnieuw.',
        toastDeleteNoteError:
            'Kon de notitie niet verwijderen. Probeer het opnieuw.',
        toastReorderError:
            'Kon de nieuwe volgorde niet opslaan. Probeer het opnieuw.',
        toastDeleteBoardError:
            'Kon het board niet verwijderen. Probeer het opnieuw.',
        toastDueDateError:
            'Kon de deadline niet bijwerken. Probeer het opnieuw.',
        toastLabelError: 'Kon de labels niet bijwerken. Probeer het opnieuw.',
        toastCreateLabelError:
            'Kon het label niet aanmaken. Probeer het opnieuw.',
        toastUpdateLabelError:
            'Kon het label niet bijwerken. Probeer het opnieuw.',
        toastDeleteLabelError:
            'Kon het label niet verwijderen. Probeer het opnieuw.',
    },
    labelManager: {
        title: 'Labels beheren',
        description: 'Maak labels om taken mee te categoriseren.',
        namePlaceholder: 'Naam van het label',
        add: 'Toevoegen',
        empty: 'Nog geen labels op dit board.',
        delete: 'Label verwijderen',
    },
    taskDetail: {
        dialogTitle: 'Taakdetails',
        descriptionPlaceholder:
            "Voeg een beschrijving toe, of typ '/' voor opties…",
    },
    noteEditor: {
        placeholder: "Schrijf iets, of typ '/' voor opties…",
        toolbarBold: 'Vet',
        toolbarItalic: 'Cursief',
        toolbarStrike: 'Doorhalen',
        toolbarBulletList: 'Opsomming',
        toolbarOrderedList: 'Genummerde lijst',
        toolbarChecklist: 'Checklist',
        toolbarQuote: 'Citaat',
        toolbarImage: 'Afbeelding',
        toolbarUndo: 'Ongedaan maken',
        toolbarRedo: 'Opnieuw',
        slashHeading2: 'Kop 2',
        slashHeading3: 'Kop 3',
        slashBulletList: 'Opsomming',
        slashOrderedList: 'Genummerde lijst',
        slashChecklist: 'Checklist (to-do)',
        slashQuote: 'Citaat',
        slashCodeBlock: 'Codeblok',
        slashDivider: 'Scheidingslijn',
        slashImage: 'Afbeelding',
        slashNoResults: 'Geen resultaten',
        uploadImageError: 'Afbeelding uploaden is mislukt.',
    },
    welcome: {
        appName: 'Anchor',
        login: 'Inloggen',
        createAccount: 'Account aanmaken',
        startBoardTitle: 'Begin met een board',
        newBoard: 'Nieuw board',
    },
    realtime: {
        viewersOne: 'Alleen jij bent hier',
        viewersOther: '{count} mensen hier',
        you: 'jij',
        editing: '{name} is aan het bewerken…',
        taskCreated: '{name} heeft een taak toegevoegd',
        boardRenamed: '{name} heeft dit board hernoemd',
        boardDeletedByOwner: '“{name}” is verwijderd door de eigenaar',
        boardSharedWithYou: '{user} heeft “{name}” met je gedeeld',
        boardAccessRevoked: 'Je toegang tot “{name}” is ingetrokken',
        roleChangedTo: {
            editor: 'Je kunt dit board nu bewerken',
            viewer: 'Je toegang tot dit board is nu alleen-lezen',
        },
    },
    public: {
        github: 'GitHub',
        navigation: {
            about: 'Over',
            privacy: 'Privacy',
            signIn: 'Inloggen',
            openWorkspace: 'Workspace openen',
        },
        footer: {
            copyright: '© {year} AnchorNotes. Gemaakt voor rustiger werken.',
        },
        about: {
            pageTitle: 'Over',
            eyebrow: 'Over',
            introduction:
                'AnchorNotes is een gedeelde werkplek voor boards, taken en notities. Zo blijft de volgende stap dicht bij het gesprek eromheen.',
            context: {
                title: 'Houd werk in context',
                description:
                    'Maak boards voor projecten, voeg taken toe wanneer ze ontstaan en bewaar nuttige notities bij het werk.',
            },
            sharing: {
                title: 'Deel alleen wat nodig is',
                description:
                    'Nodig mensen uit voor afzonderlijke boards, zodat projectwerk los blijft van de rest.',
            },
            simple: {
                title: 'Standaard eenvoudig',
                description:
                    'Het doel is een praktische plek om werk te organiseren, zonder van elk project een proces te maken.',
            },
            openSourcePrefix:
                'AnchorNotes is open source. Bekijk het project op',
            contactPrefix: 'Vragen of feedback:',
            openApp: 'AnchorNotes openen',
        },
        privacy: {
            pageTitle: 'Privacy',
            eyebrow: 'Privacy',
            heading: 'Privacyverklaring',
            lastUpdated: 'Laatst bijgewerkt op 16 juli 2026',
            scope: {
                title: 'Waar deze pagina over gaat',
                description:
                    'Deze privacyverklaring legt in duidelijke taal uit hoe informatie in AnchorNotes wordt gebruikt om de dienst te leveren. Het is een informatieve samenvatting en moet worden gelezen naast eventuele privacyvereisten die voor jouw workspace gelden.',
            },
            workspaceData: {
                title: 'Informatie in je workspace',
                description:
                    'AnchorNotes bewaart de accountgegevens, boards, taken, notities, bijlagen en samenwerkingsinformatie die jij of je medewerkers toevoegen. Deze informatie wordt gebruikt om gebruikers aan te melden, de juiste workspace-inhoud te tonen en functies zoals delen en uitnodigingen mogelijk te maken.',
            },
            access: {
                title: 'Hoe toegang werkt',
                description:
                    'De inhoud van een workspace is beschikbaar voor de accounteigenaar en voor mensen die toegang tot een board hebben gekregen. Kies medewerkers zorgvuldig en verwijder toegang wanneer die niet meer nodig is. De beveiliging van je account hangt ook af van een sterk, uniek wachtwoord en het beschermen van je inlogmethoden.',
            },
            retention: {
                title: 'Bewaren en verwijderen',
                description:
                    'Informatie blijft in AnchorNotes zolang die nodig is voor de workspace of totdat deze via de applicatie wordt verwijderd. Het verwijderen van een board, notitie, taak of account kan invloed hebben op gerelateerde workspace-inhoud. Neem voor hulp bij inzage of verwijdering van gegevens contact op met de beheerder van jouw AnchorNotes-installatie.',
            },
            changes: {
                title: 'Wijzigingen in deze verklaring',
                description:
                    'Deze pagina kan veranderen wanneer AnchorNotes verder wordt ontwikkeld. De datum bovenaan geeft aan wanneer de pagina voor het laatst is bijgewerkt. Als je de dienst na een wijziging blijft gebruiken, raden we aan de aangepaste informatie te bekijken.',
            },
            help: {
                title: 'Hulp nodig?',
                contactPrefix:
                    'Heb je vragen over privacy of jouw gegevens? Mail dan naar',
                sourcePrefix:
                    'AnchorNotes is open source. De broncode staat op',
            },
        },
    },
};
