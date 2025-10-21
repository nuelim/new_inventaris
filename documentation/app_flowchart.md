flowchart TD
    Start[Start] --> Login[User Login]
    Login --> Dashboard[Dashboard]
    Dashboard --> Catalog[Item tracking and catalog]
    Dashboard --> Stock[Real time stock monitoring]
    Dashboard --> Alerts[Low stock alerts and notifications]
    Dashboard --> Reports[Reporting and analytics dashboard]
    Dashboard --> Search[Advanced search and filtering]
    Dashboard --> ImportExport[Data import and export]
    Dashboard --> Audit[Audit trail and change history]
    Dashboard --> API[API integration and extensibility]
    Dashboard --> Settings[Role based access control]
    Dashboard --> Documentation[Documentation and onboarding guide]
    Catalog --> Dashboard
    Stock --> Dashboard
    Alerts --> Dashboard
    Reports --> Dashboard
    Search --> Dashboard
    ImportExport --> Dashboard
    Audit --> Dashboard
    API --> Dashboard
    Settings --> Dashboard
    Documentation --> Dashboard
    Dashboard --> End[End]