import { createI18n } from 'vue-i18n'

const messages = {
  en: {
    // Transport types
    bus: 'Bus',
    trolleybus: 'Trolleybus',
    tram: 'Tram',
    train: 'Train',
    home: 'Home',

    // Main UI
    publicTransport: 'Public transport table',
    from: 'From',
    to: 'To',
    search: 'Search',
    noRoutes: 'No routes available',
    back: 'Back',
    show: 'Show',
    menu: 'Menu',
    logoAlt: 'Transport Logo',
    currentTime: 'Current Time',
    routeNumber: 'Route Number',
    switchStops:"Switch stops",

    // Schedule
    workdays: 'Workdays',
    weekends: 'Weekends',
    departureTime: 'Departure Time',
    departureAt: 'Departure at {time}',
    showWorkdays: 'Show workday schedule',
    showWeekends: 'Show weekend schedule',
    stopTimes: 'Stop Times',
    tripSchedule: 'Trip Schedule',
    stopName: 'Stop Name',
    selectTrip: 'Select Trip',
    selectStop: 'Select Stop',
    stopTimesFor: 'Stop Times for',
    noStopTimesAvailable: 'No stop times available',
    noMatchingTrips: 'No matching trips found',

    // Settings
    settings: 'Settings',
    english: 'English',
    latvian: 'Latvian',
    login: 'Log in',
    logout: 'Log out',
    adminPanel: 'Admin Panel',
    profileSettings: 'Profile Settings',
    username: 'Username',
    saveChanges: 'Save Changes',
    newPassword: 'New Password',
    confirmPassword: 'Confirm Password',
    changePassword: 'Change Password',
    deleteProfile: 'Delete Profile',
    UpdatePassword: "Update Password",
    password: "Password",
    profileDeleted: "Profile deleted successfully.",
    passwordUpdated: "Password updated",
    currentUsername: "Username",
    changeUsername: "Change username",
    currentPassword: "Current password",
    DeleteProfile: "Delete profile",
    toggleTheme:"Toggle Theme",

    // Errors
    errorFetchingStops: 'Error fetching stops:',
    errorFetchingStopTimes: 'Error fetching stop times:',
    networkError: 'Network response was not ok',
    missingDataForTimeClick: 'Missing required data for time click',
    pleaseEnterValues: "Please enter both 'From' and 'To' values.",
    userNotFound: "We can't find a user with that email address",

    // Auth
    register: 'Register',
    email: 'Email',
    emailPlaceholder: 'Enter your email',
    passwordPlaceholder: 'Enter your password',
    rememberMe: 'Remember me',
    forgotPassword: 'Forgot password?',
    noAccount: "Don't have an account?",
    alreadyRegistered: 'Already registered?',
    or: 'OR',
    loginWithGoogle: 'Login with Google',
    loginWithGitHub: 'Login with GitHub',

    // Password reset
    forgotPasswordInstructions: 'Enter your email address and we will send you a password reset link.',
    resetPassword: 'Reset Password',
    passwordResetLinkSent: 'A password reset link has been sent to your email address.',
    passwordResetSuccess: 'Your password has been reset successfully!',
    // Updated password requirements message
    passwordRequirements: 'Password must be at least 8 characters and contain at least one capital letter.',

    // Email verification
    emailVerification: 'Email Verification',
    verifyEmailMessage: 'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.',
    verificationLinkSent: 'A new verification link has been sent to the email address you provided during registration.',
    resendVerificationEmail: 'Resend Verification Email',

    // Language switcher
    currentLanguage: 'Current Language',
    selectLanguage: 'Select Language',
    emailAddress: 'Email Address',

    //maps
    viewOnMap: "View on Map",
    viewRouteOnMap: "View this route on Google Maps",
    stopSequence: 'Sequence',
    lastStop: 'Last Stop',
    hideAdditionalStops: 'Hide additional stops',
    showAdditionalStops: 'Show additional stops',
    toggleStops: 'Toggle stops',
    back: 'Back',

    // Admin - Users
    userManagement: 'User Management',
    id: 'ID',
    admin: 'Admin',
    actions: 'Actions',
    deleteUser: 'Delete',
    confirmDeleteUser: 'Are you sure you want to delete this user?',
    toggleAdminStatus: 'Toggle admin status',
    adminUsersTitle: 'Admin - Users',
    create:"create",
    confirmDeleteTitle:"Confirm delete title",
    confirmDeleteMessage:"Confirm delete message",
    createUser:"Create user",
    edit:"edit",
    editUserTitle:"Edit user title",
    editUser:"Edit user",
    createUserTitle:"Create User title",

    loading: 'Loading...',
    save: 'Save',
    cancel: 'Cancel',
    update: 'Update',

    //Save Times
    saveSelectedTimes: "save Selected Times",
    selectStopTimesToSave: "Select Stop Times To Save",
    timesSavedSuccessfully: "Times Saved Successfully",
    loginToSaveTimes:"Login to save times",
    stop:"Stop",
    savedTimes:"Saved times",
    delete:"delete",
    mySavedTimes:"My saved times",
    trip:"Trip",
    noSavedTimesYet:"No saved times yet",

    // --- NEW: Validation Error Messages ---
    validation: {
      password: {
        min: 'The password field must be at least {min} characters.',
        mixed_case: 'The password field must contain at least one uppercase and one lowercase letter.',
        numbers: 'The password field must contain at least one number.',
        symbols: 'The password field must contain at least one symbol.',
        required: 'The password field is required.',
        confirmed: 'The password confirmation does not match.',
      },
      email: {
        required: 'The email field is required.',
        email: 'The email field must be a valid email address.',
        unique: 'The email has already been taken.',
      },
      username: {
        required: 'The username field is required.',
        unique: 'The username has already been taken.',
      },
      // General validation messages (Laravel often uses these for generic errors)
      required: 'The {attribute} field is required.',
      unique: 'The {attribute} has already been taken.',
      string: 'The {attribute} field must be a string.',
      max: 'The {attribute} field must not be greater than {max} characters.',
    }
  },
  lv: {
    // Transport types
    bus: 'Autobuss',
    trolleybus: 'Trolejbuss',
    tram: 'Tramvajs',
    train: 'Vilciens',
    home: 'Sākums',

    // Main UI
    publicTransport: 'Publiskā transporta saraksts',
    from: 'No',
    to: 'Uz',
    search: 'Meklēt',
    noRoutes: 'Nav pieejamu maršrutu',
    back: 'Atpakaļ',
    show: 'Rādīt',
    menu: 'Izvēlne',
    logoAlt: 'Transporta logo',
    currentTime: 'Pašreizējais laiks',
    routeNumber: 'Maršruta numurs',
    switchStops:"Mainīt pieturu",

    // Schedule
    workdays: 'Darbdienas',
    weekends: 'Brīvdienas',
    departureTime: 'Atiešanas laiks',
    departureAt: 'Atiešana pulksten {time}',
    showWorkdays: 'Rādīt darbdienu sarakstu',
    showWeekends: 'Rādīt brīvdienu sarakstu',
    stopTimes: 'Pieturas Laiki',
    tripSchedule: 'Maršruta Saraksts',
    stopName: 'Pieturas Nosaukums',
    selectTrip: 'Izvēlieties maršrutu',
    selectStop: 'Izvēlieties pieturu',
    stopTimesFor: 'Pieturas laiki priekš',
    noStopTimesAvailable: 'Nav pieejamu pieturas laiku',
    noMatchingTrips: 'Nav atrastu atbilstošu maršrutu',

    // Settings
    settings: 'Iestatījumi',
    english: 'Angļu',
    latvian: 'Latviešu',
    login: 'Pieteikties',
    logout: 'Izrakstīties',
    adminPanel: 'Administrācijas panelis',
    profileSettings: 'Profila iestatījumi',
    username: 'Lietotājvārds',
    saveChanges: 'Saglabāt izmaiņas',
    newPassword: 'Jauna parole',
    confirmPassword: 'Apstiprināt paroli',
    changePassword: 'Mainīt paroli',
    deleteProfile: 'Dzēst profilu',
    UpdatePassword: "Paroles maiņa",
    password: "Parole",
    profileDeleted: "Profils veiksmīgi izdzēsts",
    passwordUpdated: "Parole atjaunota",
    currentUsername: "Lietotājvārds",
    changeUsername: "Mainīt Lietotājvārdu",
    currentPassword: "Tagadējā parole",
    DeleteProfile: "Dzēst profilu",
    toggleTheme:"Ieslēgt motīvu",

    // Errors
    errorFetchingStops: 'Kļūda ielādējot pieturas:',
    errorFetchingStopTimes: 'Kļūda ielādējot pieturas laikus:',
    networkError: 'Tīkla atbilde nav saņemta',
    missingDataForTimeClick: 'Trūkst nepieciešamie dati laika izvēlei',
    pleaseEnterValues: "Lūdzu, ievadiet gan 'No', gan 'Uz' vērtības.",
    userNotFound: "Mēs nevaram atrast lietotāju ar šo e-pasta adresi",

    // Auth
    register: 'Reģistrēties',
    email: 'E-pasts',
    emailPlaceholder: 'Ievadiet savu e-pastu',
    passwordPlaceholder: 'Ievadiet savu paroli',
    rememberMe: 'Atcerēties mani',
    forgotPassword: 'Aizmirsāt paroli?',
    noAccount: 'Nav konta?',
    alreadyRegistered: 'Jau reģistrējies?',
    or: 'VAI',
    loginWithGoogle: 'Pieteikties ar Google',
    loginWithGitHub: 'Pieteikties ar GitHub',

    // Password reset
    forgotPasswordInstructions: 'Ievadiet savu e-pasta adresi, un mēs jums nosūtīsim paroles atiestatīšanas saiti.',
    resetPassword: 'Atiestatīt paroli',
    passwordResetLinkSent: 'Paroles atiestatīšanas saite ir nosūtīta uz jūsu e-pasta adresi.',
    passwordResetSuccess: 'Jūsu parole ir veiksmīgi atiestatīta!',
    // Updated password requirements message
    passwordRequirements: 'Parolei jābūt vismaz 8 rakstzīmes garai un jāsatur vismaz viens lielais burts.',

    // Email verification
    emailVerification: 'E-pasta verifikācija',
    verifyEmailMessage: 'Paldies par reģistrēšanos! Pirms sākat, lūdzu, verificējiet savu e-pasta adresi, noklikšķinot uz saites, ko mēs tikko nosūtījām. Ja nesaņēmāt e-pastu, mēs ar prieku nosūtīsim vēl vienu.',
    verificationLinkSent: 'Jauna verifikācijas saite ir nosūtīta uz jūsu reģistrācijas laikā norādīto e-pasta adresi.',
    resendVerificationEmail: 'Atkārtoti nosūtīt verifikācijas e-pastu',

    // Language switcher
    currentLanguage: 'Pašreizējā valoda',
    selectLanguage: 'Izvēlēties valodu',
    emailAddress: 'E-pasta adrese',

    //maps
    viewOnMap: "Skatīt kartē",
    viewRouteOnMap: "Skatīt šo maršrutu Google Maps",
    stopSequence: 'Secība',
    lastStop: 'Pēdējā pietura',
    hideAdditionalStops: 'Paslēpt papildu pieturas',
    showAdditionalStops: 'Rādīt papildu pieturas',
    toggleStops: 'Pārslēgt pieturas',
    back: 'Atpakaļ',

    // Admin - Users
    userManagement: 'Lietotāju pārvaldība',
    id: 'ID',
    admin: 'Administrators',
    actions: 'Darbības',
    deleteUser: 'Dzēst',
    confirmDeleteUser: 'Vai tiešām vēlaties dzēst šo lietotāju?',
    toggleAdminStatus: 'Pārslēgt administratora statusu',
    adminUsersTitle: 'Administrēšana - Lietotāji',
    create:"veidot",
    confirmDeleteTitle:"Piekrist dzēst nosaukumu",
    confirmDeleteMessage:"Piekrist dzēst īzziņa",
    createUser:"Izveidot lietotāju",
    edit:"rediģēt",
    editUserTitle:"Rediģēt lietotāja vārdu",
    editUser:"rediģēt lietotāju",
    createUserTitle:"Izveidot lietotāja vārdu",

    loading: 'Ielādējas...',
    save: 'Saglabāt',
    cancel: 'Atcelt',
    update: 'Atjaunināt',

    //Save Times
    saveSelectedTimes: "Saglabāt izvēlētps laikus",
    selectStopTimesToSave: "Izvēlies laikus ko saglabāt",
    timesSavedSuccessfully: "Laiki saglabāti",
    loginToSaveTimes:"Ielagojies lai saglabātu laikus",
    stop:"Pietura",
    savedTimes:"Saglabātie laiki",
    delete:"Izdzēst",
    mySavedTimes:"Mani saglabātie laiki",
    trip:"Maršruts",
    noSavedTimesYet:"Nav saglabātu laiku pašlaik",

    // --- NEW: Validation Error Messages ---
    validation: {
      password: {
        min: 'Parolei jābūt vismaz {min} rakstzīmes garai.',
        mixed_case: 'Parolei jāsatur vismaz viens lielais un viens mazais burts.',
        numbers: 'Parolei jāsatur vismaz viens cipars.',
        symbols: 'Parolei jāsatur vismaz viens simbols.',
        required: 'Paroles lauks ir obligāts.',
        confirmed: 'Paroles apstiprinājums nesakrīt.',
      },
      email: {
        required: 'E-pasta lauks ir obligāts.',
        email: 'E-pasta laukam jābūt derīgai e-pasta adresei.',
        unique: 'Šis e-pasts jau ir reģistrēts.',
      },
      username: {
        required: 'Lietotājvārda lauks ir obligāts.',
        unique: 'Šis lietotājvārds jau ir aizņemts.',
      },
      // General validation messages (Laravel often uses these for generic errors)
      required: 'Lauks "{attribute}" ir obligāts.',
      unique: 'Lauks "{attribute}" jau ir aizņemts.',
      string: 'Laukam "{attribute}" jābūt teksta virknei.',
      max: 'Lauks "{attribute}" nedrīkst pārsniegt {max} rakstzīmes.',
    }
  }
}

// Create I18n instance
const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages
})

export default i18n
