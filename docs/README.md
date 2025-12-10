contact me on fb if theres any error or something you dont understand: jp tuangco

# 🎓 University Admission Portal

A comprehensive web-based admission portal for universities built with PHP, MySQL, and Bootstrap. This system allows students to register, upload requirements, fill out admission forms, and generate PDFs, while admins can manage applicants, verify documents, and access reports.

## ✨ Features

### Student Features
- **Registration & Login**: Secure student account creation and authentication with separate name fields (Last, First, Middle)
- **Dashboard**: Progress tracker with step-by-step admission process and approval status
- **Requirements Upload**: Different requirements for Freshman vs Transferee with automatic approval workflow
- **Pre-Admission Form**: Comprehensive form with personal, family, and educational information
- **Test Permit**: Schedule and generate entrance exam permits with admin approval system
- **Document Center**: View, download, and print all documents with print functionality
- **PDF Generation**: Automatic PDF generation for forms and permits matching F1-A format
- **Approval Tracking**: Real-time status updates for test permits and document approvals

### Admin Features
- **Admin Dashboard**: Overview of applicants with statistics and test permit approvals
- **Applicant Management**: View, search, filter, and manage student applications
- **Document Verification**: Approve/reject uploaded requirements with remarks
- **Test Permit Management**: Approve/reject test permits with automatic document approval
- **Admission Forms Management**: View and manage all submitted admission forms
- **User Management**: Add new students and admin accounts
- **Statistics & Analytics**: Comprehensive statistics with charts and reports
- **Settings Management**: Configure test permit settings and system preferences
- **Status Management**: Update student application status
- **Approval Workflow**: Streamlined approval process for test permits and documents

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5.3, HTML5, CSS3, JavaScript
- **PDF Generation**: TCPDF (replacing FPDF)
- **Charts**: Chart.js for statistics visualization
- **Server**: XAMPP (Apache, MySQL, PHP)

## 📋 Requirements

- XAMPP or similar LAMP/WAMP stack
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser with JavaScript enabled
- GD extension for image processing (for PDF generation)

## 🚀 Installation

### 1. Download and Setup XAMPP
1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP and start Apache and MySQL services

### 2. Clone/Download the Project
1. Download or clone this repository
2. Extract the files to `C:\xampp\htdocs\Admission Portal\` (or your XAMPP htdocs directory)

### 3. Database Setup
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `university_portal`
3. Import the database schema from `database/university_portal.sql`
4. Run the migration script `database/add_ethnic_others_specify_field.sql` to add the ethnic_others_specify field

### 4. Configuration
1. The database configuration is already set in `config/database.php`
2. Default admin credentials:
   - Username: `admin`
   - Password: `password` (hashed in database)

### 5. File Permissions
Ensure the following directories are writable:
- `uploads/`
- `generated_pdfs/`
- `uploads/requirements/`
- `generated_pdfs/admission_forms/`
- `generated_pdfs/test_permits/`

## 📁 Project Structure

```
Admission Portal/
├── admin/                  # Admin panel files
│   ├── dashboard.php      # Admin dashboard with statistics
│   ├── applicants.php     # Manage all applicants
│   ├── test_permits.php   # Test permit management with filters
│   ├── admission_forms.php # Admission forms management
│   ├── add_student.php    # Add new student accounts
│   ├── add_admin.php      # Add new admin accounts
│   ├── test_permit_stats.php # Statistics and analytics
│   ├── test_permit_settings.php # System settings
│   ├── login.php         # Admin login
│   └── logout.php        # Admin logout
├── assets/               # Static assets
│   └── css/
│       └── style.css     # Custom styles
├── config/               # Configuration files
│   ├── config.php        # Main configuration
│   └── database.php      # Database connection
├── database/             # Database files
│   ├── university_portal.sql # Main database schema
│   ├── add_ethnic_others_specify_field.sql # Migration for ethnic field
│   └── run_ethnic_others_migration.php # Migration runner
├── includes/             # PHP includes
│   ├── auth.php          # Authentication functions
│   ├── requirements.php  # Requirements management
│   ├── admission_form.php # Admission form functions
│   ├── test_permit.php   # Test permit functions
│   ├── test_permit_pdf.php # Test permit PDF generation
│   └── fpdf/             # FPDF library (legacy)
├── student/              # Student panel files
│   ├── dashboard.php     # Student dashboard with progress tracking
│   ├── register.php      # Student registration
│   ├── login.php         # Student login
│   ├── requirements.php  # Upload requirements
│   ├── admission_form.php # Pre-admission form
│   ├── test_permit.php   # Test permit request
│   ├── documents.php     # Document center
│   └── logout.php        # Student logout
├── uploads/              # File uploads directory
├── generated_pdfs/       # Generated PDF files
│   ├── admission_forms/  # Admission form PDFs
│   └── test_permits/     # Test permit PDFs
├── vendor/               # Third-party libraries
│   └── tecnickcom/
│       └── tcpdf/        # TCPDF library
├── view_pdf.php          # PDF viewer for admission forms
├── view_test_permit.php  # PDF viewer for test permits
├── download_pdf.php      # Download admission form PDFs
├── download_test_permit.php # Download test permit PDFs
├── index.php             # Home page
└── README.md             # This file
```

## 🎨 Design Features

- **Green Theme**: Professional university green color scheme (#006633)
- **Responsive Design**: Mobile-first, works on all devices
- **Modern UI**: Clean, intuitive interface with Bootstrap 5
- **Progress Tracking**: Visual progress indicators for students
- **Status Badges**: Color-coded status indicators
- **Interactive Elements**: Hover effects, animations, and smooth transitions
- **Statistics Dashboard**: Beautiful charts and analytics with Chart.js
- **Print Functionality**: Print and download PDFs directly from the interface

## 📊 Database Schema

### Tables
- `students` - Student information and accounts (with separate name fields)
- `admission_forms` - Pre-admission form data with comprehensive fields including ethnic affiliation
- `test_permits` - Test permit information with approval system
- `requirements` - Uploaded requirement files with approval status
- `admins` - Admin accounts with role-based access
- `test_permit_settings` - System configuration settings

### Key Features
- **Separate Name Fields**: Last Name, First Name, Middle Name structure
- **Approval System**: Test permit approval with automatic document approval
- **Status Tracking**: Comprehensive status management for all entities
- **File Management**: Secure file upload and storage system
- **Audit Trail**: Timestamps and approval tracking

## 🔐 Security Features

- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session management with proper authentication
- File upload validation and security
- Access control for different user types
- Role-based permissions (Admin, SuperAdmin)

## 📱 User Workflows

### Student Workflow
1. **Register** → Create account with separate name fields (Last, First, Middle)
2. **Upload Requirements** → Upload all required documents (Freshman/Transferee specific)
3. **Fill Admission Form** → Complete comprehensive personal and educational information
4. **Generate Test Permit** → Schedule entrance examination with admin approval
5. **Track Progress** → Monitor application status and approval workflow
6. **Print Documents** → Download and print all documents for university visit

### Admin Workflow
1. **Login** → Access admin panel with enhanced dashboard
2. **Review Applicants** → View and filter student applications
3. **Verify Documents** → Approve/reject uploaded requirements with remarks
4. **Approve Test Permits** → Review and approve test permits (auto-approves documents)
5. **Manage Users** → Add new students and admin accounts
6. **View Statistics** → Access comprehensive analytics and reports
7. **Update Status** → Change student application status
8. **Configure Settings** → Manage system preferences

## 🎯 Key Features

### For Students
- Step-by-step admission process with progress tracking
- Real-time progress tracking and approval status
- Document upload with validation (Freshman/Transferee specific)
- PDF generation for forms matching F1-A format
- Mobile-responsive design
- Status notifications and approval tracking
- Print functionality for all documents
- Age calculation and form validation
- Document center with organized file management

### For Admins
- Comprehensive applicant management with search and filters
- Document verification system with approval workflow
- Test permit approval system with automatic document approval
- User management (add students and admins)
- Statistical reports and analytics with interactive charts
- Search and filter capabilities across all modules
- Bulk operations for efficient management
- Enhanced dashboard with approval statistics
- Settings management for system configuration
- Print and download functionality for all documents

## 🔧 Customization

### Name Field Structure
The system uses separate fields for Last Name, First Name, and Middle Name instead of a single "Full Name" field. The database automatically generates a combined name field for backward compatibility.

### Approval System
The system includes a comprehensive approval workflow where admins can approve test permits, which automatically approves all uploaded documents for that student. This streamlines the approval process and ensures consistency.

### PDF Generation
The system generates PDFs that match the official F1-A Application for Admission format, including all required sections and proper formatting for university submission. Uses TCPDF for enhanced PDF generation capabilities.

### Adding New Requirements
Edit `includes/requirements.php` to modify the requirements list for different student types.

### Modifying Forms
Update the form fields in `student/admission_form.php` and corresponding database schema.

### Changing Theme Colors
Modify the CSS variables in `assets/css/style.css` to change the color scheme.

### Adding New Admin Features
Create new PHP files in the `admin/` directory and update the navigation.

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check if MySQL is running in XAMPP
   - Verify database credentials in `config/database.php`
   - Ensure database name is `university_portal`

2. **File Upload Issues**
   - Check directory permissions for `uploads/` folder
   - Verify PHP upload settings in `php.ini`
   - Ensure GD extension is enabled for image processing

3. **PDF Generation Errors**
   - Ensure `generated_pdfs/` directory is writable
   - Check TCPDF library installation
   - Verify GD extension is enabled for image processing

4. **Session Issues**
   - Clear browser cookies and cache
   - Check PHP session configuration

5. **Name Field Issues (htmlspecialchars() deprecated warning)**
   - The system now handles null values properly
   - All htmlspecialchars() calls include null coalescing operators

6. **PDF Generation Issues**
   - If PDFs fail to load or display, check the PDF directories exist
   - Ensure `generated_pdfs/` directory and subdirectories are writable
   - Use `view_pdf.php` and `view_test_permit.php` to view PDFs

7. **Database Migration Issues**
   - Run `database/add_ethnic_others_specify_field.sql` for ethnic affiliation field
   - Check database connection and table structure

## 🧪 Testing & Debugging

### Testing Utilities
The system includes several testing utilities to help diagnose and fix issues:

- **`view_pdf.php`** - PDF viewer for admission forms
- **`view_test_permit.php`** - PDF viewer for test permits
- **`download_pdf.php`** - Download admission form PDFs
- **`download_test_permit.php`** - Download test permit PDFs

### Quick Testing Steps
1. **Database Test**: Check database connection and table structure
2. **PDF Test**: Generate and view PDFs through the interface
3. **Form Test**: Submit forms and verify data storage

## 📞 Support

For support and questions:
- Check the troubleshooting section above
- Review the code comments for implementation details
- Ensure all requirements are met
- Verify database schema and migrations are applied

## 📄 License

This project is open source and available under the MIT License.

## 🚀 Recent Updates & Current Features

### Current Features (v3.0)
- ✅ **Enhanced Admin Dashboard**: Statistics cards with colored backgrounds
- ✅ **User Management**: Add new students and admin accounts
- ✅ **Statistics & Analytics**: Interactive charts with Chart.js
- ✅ **Filter System**: Search and filter for test permits and admission forms
- ✅ **Print Functionality**: Print and download PDFs directly from interface
- ✅ **TCPDF Integration**: Enhanced PDF generation capabilities
- ✅ **Ethnic Affiliation**: Support for ethnic affiliation with "Others" specification
- ✅ **Document Center**: Organized document management for students
- ✅ **Approval Workflow**: Streamlined approval process
- ✅ **Responsive Design**: Mobile-friendly interface
- ✅ **Security Enhancements**: Improved authentication and authorization

### Future Enhancements
- Email notifications for status changes
- Advanced reporting with more chart types
- Bulk document processing
- Integration with external systems
- Mobile app development
- Advanced search and filtering
- Document templates customization
- Multi-language support
- Real-time notifications
- Advanced analytics dashboard
- Email integration for notifications
- Document versioning system

---

**Note**: This is a complete university admission portal system with comprehensive features for both students and administrators. The system includes modern UI/UX design, robust security features, and extensive functionality for managing the entire admission process.
