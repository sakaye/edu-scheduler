# PRD Writing Prompt: Modern Higher Education Scheduling Platform

## Context & Objective
You are writing a Product Requirements Document (PRD) for a modern, cloud-native SaaS replacement for the SARS (Scheduling and Reporting System) appointment scheduling software widely used in higher education. SARS is a legacy system built on .NET/ASP with Microsoft SQL that has served colleges and universities since 1995 but suffers from significant technical debt and user experience limitations.

## Target Market
- **Primary**: Colleges and universities in North America
- **Initial Focus**: Institutions currently using Banner ERP systems
- **User Personas**: Academic advisors, counselors, tutors, administrators, support staff, and students

## Legacy System Analysis (SARS)
The current system includes these components that must be modernized and improved:

### Core Components to Replace:
1. **SARS Anywhere** - Grid-based appointment scheduling system
2. **SARS-TRACK** - Self-service check-in kiosks with card swipe capability
3. **SARS-MSGS** - Automated messaging and reminder system
4. **eSARS** - Student self-scheduling web portal
5. **Resource Planning** - Calendar-based resource and utilization planning
6. **Reporting Engine** - Student service utilization analytics

### Critical Pain Points to Address:
- **Outdated UI/UX**: Student-facing interface described as "dated" and "arcane"
- **Limited Mobile Support**: Poor mobile responsiveness and no native apps
- **Reporting Limitations**: Inflexible reporting with limited customization
- **Integration Gaps**: No 2-way calendar sync (Outlook, Google), clunky API
- **Technical Debt**: Legacy .NET/ASP architecture limiting scalability
- **User Management**: Non-intuitive security roles and permissions system
- **Setup Complexity**: "Clunky" administrative configuration process

## Technical Requirements

### Architecture:
- **Cloud-Native SaaS**: Multi-tenant architecture with modern web technologies
- **API-First Design**: RESTful APIs enabling seamless integrations
- **Real-Time Capabilities**: Live updates, notifications, and synchronization
- **Mobile-First**: Responsive web design + progressive web app capabilities
- **Scalability**: Horizontal scaling to support large university systems

### Integration Priorities:
1. **Banner ERP** - Primary student information system integration
2. **Calendar Systems** - Bi-directional sync with Outlook, Google Calendar
3. **Identity Providers** - SSO with SAML, OAuth, LDAP
4. **Communication Platforms** - SMS, email, push notifications
5. **Video Conferencing** - Zoom, Teams, WebEx auto-scheduling

## Key Success Metrics
Define how success will be measured:
- User adoption rates (staff and student)
- Appointment no-show reduction
- Administrative time savings
- Student satisfaction scores
- System uptime and performance
- Integration reliability

## Competitive Landscape
Consider these factors:
- **SARS Strengths**: Proven in higher ed, comprehensive feature set, strong support
- **Market Gap**: Modern UX, mobile-first design, advanced analytics
- **Differentiation Opportunities**: AI-powered scheduling optimization, advanced reporting, modern integrations

## PRD Structure Requirements

Your PRD should include these sections:

### 1. Executive Summary
- Problem statement referencing SARS limitations
- Solution vision for modern cloud platform
- Key business outcomes and success metrics

### 2. User Stories & Use Cases
Detail workflows for:
- **Students**: Self-scheduling, check-in, rescheduling, notifications
- **Advisors/Counselors**: Schedule management, student notes, availability
- **Administrators**: System configuration, reporting, user management
- **IT Staff**: Integration setup, security management, system monitoring

### 3. Functional Requirements
For each core component, specify:
- **Scheduling Engine**: Grid-based and calendar views, recurring appointments, waitlists
- **Self-Service Portal**: Student booking, mobile check-in, appointment history
- **Communication System**: Multi-channel notifications, automated reminders
- **Analytics & Reporting**: Customizable dashboards, utilization metrics, trend analysis
- **Resource Management**: Room/equipment booking, staff availability planning

### 4. Technical Specifications
- System architecture and technology stack recommendations
- API specifications for Banner ERP integration
- Security and compliance requirements (FERPA, accessibility)
- Performance benchmarks and scalability targets

### 5. User Experience Requirements
- Mobile-responsive design principles
- Accessibility compliance (WCAG 2.1 AA)
- Intuitive navigation reducing training time
- Modern UI component library and design system

### 6. Implementation Approach
- Phased rollout strategy starting with core scheduling
- Migration approach from existing SARS installations
- Change management and user adoption strategy

## Tone and Approach
- **Be Specific**: Reference actual SARS limitations and user feedback
- **Focus on Outcomes**: Emphasize student success and operational efficiency
- **Dual Interface Strategy**: Clearly distinguish between student experience (Livewire/Volt + FluxUI) and staff workflows (Filament admin)
- **Consider Constraints**: Higher ed budget cycles, IT security requirements
- **Think Long-term**: Platform extensibility and future feature expansion

## Success Criteria for PRD Quality
Your PRD should:
- Clearly articulate the value proposition vs. continuing with SARS
- Specify Laravel 12 + Filament 4 + Livewire/Volt + FluxUI implementation approach
- Detail Filament's many-to-many multi-tenancy setup using "team" model for department/team groupings
- Provide detailed user workflows that eliminate current friction points
- Include technical specifications enabling accurate development estimates
- Address compliance and security requirements specific to higher education
- Present a realistic prototype development timeline focusing on core functionality