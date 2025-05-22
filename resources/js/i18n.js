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
    UpdatePassword:"Update Password",
    deleteProfile: "Delete Profile",
    password: "Password",
    profileDeleted: "Profile deleted successfully.",
    passwordUpdated:"Password updated",
    currentUsername:"Username",
    changeUsername:"Change username",
    currentPassword:"Current password",
    DeleteProfile:"Delete profile",

    // Errors
    errorFetchingStops: 'Error fetching stops:',
    errorFetchingStopTimes: 'Error fetching stop times:',
    networkError: 'Network response was not ok',
    missingDataForTimeClick: 'Missing required data for time click',
    pleaseEnterValues: "Please enter both 'From' and 'To' values.",
    userNotFound: "We can't find a user with that email address",

    // Auth
    login: 'Log in',
    logout: 'Log Out',
    register: 'Register',
    email: 'Email',
    emailPlaceholder: 'Enter your email',
    password: 'Password',
    passwordPlaceholder: 'Enter your password',
    rememberMe: 'Remember me',
    forgotPassword: 'Forgot password?',
    noAccount: "Don't have an account?",
    username: 'Username',
    confirmPassword: 'Confirm Password',
    alreadyRegistered: 'Already registered?',
    or: 'OR',
    loginWithGoogle: 'Login with Google',
    loginWithGitHub: 'Login with GitHub',

    // Password reset
    forgotPasswordInstructions: 'Enter your email address and we will send you a password reset link.',
    resetPassword: 'Reset Password',
    passwordResetLinkSent: 'A password reset link has been sent to your email address.',
    passwordResetSuccess: 'Your password has been reset successfully!',
    passwordRequirements: 'Password must be at least 8 characters',

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
    username: 'Username',
    email: 'Email',
    admin: 'Admin',
    actions: 'Actions',
    deleteUser: 'Delete',
    confirmDeleteUser: 'Are you sure you want to delete this user?',
    toggleAdminStatus: 'Toggle admin status',
    adminUsersTitle: 'Admin - Users',


    loading: 'Loading...',
    save: 'Save',
    cancel: 'Cancel',
    update: 'Update',

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
    UpdatePassword:"Paroles maiņa",
    DeleteProfile:"Izdzēst profilu",
    password: "Parole",
    profileDeleted: "Profils veiksmīgi izdzēsts",
    passwordUpdated:"Parole atjaunota",
    currentUsername:"Lietotājvārds",
    changeUsername:"Mainīt Lietotājvārdu",
    currentPassword:"Tagadējā parole",
    DeleteProfile:"Dzēst profilu",

    // Errors
    errorFetchingStops: 'Kļūda ielādējot pieturas:',
    errorFetchingStopTimes: 'Kļūda ielādējot pieturas laikus:',
    networkError: 'Tīkla atbilde nav saņemta',
    missingDataForTimeClick: 'Trūkst nepieciešamie dati laika izvēlei',
    pleaseEnterValues: "Lūdzu, ievadiet gan 'No', gan 'Uz' vērtības.",
    userNotFound: "Mēs nevaram atrast lietotāju ar šo e-pasta adresi",

    // Auth
    login: 'Pieteikties',
    logout: 'Iziet',
    register: 'Reģistrēties',
    email: 'E-pasts',
    emailPlaceholder: 'Ievadiet savu e-pastu',
    password: 'Parole',
    passwordPlaceholder: 'Ievadiet savu paroli',
    rememberMe: 'Atcerēties mani',
    forgotPassword: 'Aizmirsāt paroli?',
    noAccount: 'Nav konta?',
    username: 'Lietotājvārds',
    confirmPassword: 'Apstiprināt paroli',
    alreadyRegistered: 'Jau reģistrējies?',
    or: 'VAI',
    loginWithGoogle: 'Pieteikties ar Google',
    loginWithGitHub: 'Pieteikties ar GitHub',

    // Password reset
    forgotPasswordInstructions: 'Ievadiet savu e-pasta adresi, un mēs jums nosūtīsim paroles atiestatīšanas saiti.',
    resetPassword: 'Atiestatīt paroli',
    passwordResetLinkSent: 'Paroles atiestatīšanas saite ir nosūtīta uz jūsu e-pasta adresi.',
    passwordResetSuccess: 'Jūsu parole ir veiksmīgi atiestatīta!',
    passwordRequirements: 'Parolei jābūt vismaz 8 simbolus garai',

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
    username: 'Lietotājvārds',
    email: 'E-pasts',
    admin: 'Administrators',
    actions: 'Darbības',
    deleteUser: 'Dzēst',
    confirmDeleteUser: 'Vai tiešām vēlaties dzēst šo lietotāju?',
    toggleAdminStatus: 'Pārslēgt administratora statusu',
    adminUsersTitle: 'Administrēšana - Lietotāji',



    loading: 'Ielādējas...',
    save: 'Saglabāt',
    cancel: 'Atcelt',
    update: 'Atjaunināt',

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
