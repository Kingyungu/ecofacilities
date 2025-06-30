# ecoBuddy ⚡ - Ecological Facilities Management System

A full-stack web application for discovering, managing, and tracking ecological facilities with interactive mapping and community-driven status updates.

![PHP](https://img.shields.io/badge/PHP-7.0+-777BB4?style=flat&logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript&logoColor=black)
![SQLite](https://img.shields.io/badge/SQLite-003B57?style=flat&logo=sqlite&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat&logo=bootstrap&logoColor=white)
![Leaflet](https://img.shields.io/badge/Leaflet.js-199900?style=flat&logo=leaflet&logoColor=white)

## 🌍 About

ecoBuddy helps users discover and manage ecological facilities like recycling centers, EV charging stations, and sustainable transport hubs. The platform features real-time mapping, advanced search capabilities, and community-driven status updates.

## ✨ Key Features

- 🗺️ **Interactive Mapping** - Real-time facility markers with Leaflet.js
- 🔍 **Advanced Search** - Multi-parameter filtering with infinite scroll
- 👥 **User Management** - Role-based authentication system
- 📊 **CRUD Operations** - Complete facility management
- 💬 **Status System** - Community-driven facility updates
- 📱 **Responsive Design** - Mobile-first Bootstrap interface

## 🛠️ Tech Stack

### Frontend
- HTML5, CSS3, Bootstrap 5
- Vanilla JavaScript (ES6+)
- Leaflet.js for interactive mapping
- AJAX for dynamic content

### Backend
- PHP 7.0+ with OOP principles
- SQLite database
- PDO for secure database operations
- RESTful API design

### Architecture
- Model-View-Controller (MVC) pattern
- Singleton database pattern
- Data Access Object (DAO) pattern
- Progressive enhancement

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/ecobuddy.git
   cd ecobuddy
   ```

2. **Set up web server**
   - Ensure PHP 7.0+ is installed
   - Configure web server to serve from project root
   - Ensure SQLite PDO extension is enabled

3. **Database setup**
   ```bash
   # Database file is included in db/ecobuddy.sqlite
   # Ensure web server has read/write permissions
   chmod 664 db/ecobuddy.sqlite
   chmod 775 db/
   ```

4. **Access the application**
   - Navigate to your local server URL
   - Default login credentials are in the mock data

## 📁 Project Structure

```
ecobuddy/
├── Models/              # Data access layer
│   ├── Database.php     # Singleton database connection
│   ├── EcoFacility.php  # Facility entity
│   ├── EcoFacilitySet.php # Facility operations
│   ├── CategorySet.php  # Category management
│   └── User.php         # Authentication
├── Views/               # Presentation layer
│   ├── template/        # Header/footer templates
│   ├── index.phtml      # Main facility listing
│   ├── map.phtml        # Interactive map page
│   └── manage.phtml     # Admin interface
├── api/                 # RESTful API endpoints
│   ├── facilities.php   # Facility API
│   └── status.php       # Status API
├── js/                  # Client-side JavaScript
│   ├── MapManager.js    # Map functionality
│   ├── InfiniteScrollManager.js # Infinite scroll
│   └── StatusManager.js # Status updates
├── css/                 # Stylesheets
├── db/                  # SQLite database
└── *.php               # Controllers
```

## 🔐 Security Features

- **CSRF Protection** - Token-based request validation
- **Input Sanitization** - All user inputs filtered
- **SQL Injection Prevention** - Prepared statements
- **XSS Prevention** - Output escaping
- **Role-based Access Control** - Permission management

## 🎯 API Endpoints

### Facilities API (`/api/facilities.php`)
- `GET ?action=list` - Paginated facility listing
- `GET ?action=search` - Search with filters
- `GET ?action=get&id=X` - Get specific facility

### Status API (`/api/status.php`)
- `GET ?action=get&facilityId=X` - Get facility status
- `POST action=save` - Update facility status

## 🧪 Testing

The application includes:
- Input validation testing
- Security testing (CSRF, XSS, SQL injection)
- Cross-browser compatibility
- Mobile responsiveness testing

## 📊 Database Schema

```sql
-- Core tables
ecoFacilities     # Main facility data
ecoCategories     # Facility categories
ecoUser          # User accounts
ecoFacilityStatus # Status updates
```

## 🎨 Features Showcase

### Interactive Mapping
- Leaflet.js integration with custom markers
- Marker clustering for performance
- User geolocation support
- Real-time facility popups

### Advanced Search
- Text search across multiple fields
- Category-based filtering
- Location-based filtering
- Infinite scroll with memory management

### Status Management
- Community-driven updates
- Real-time AJAX submissions
- Historical tracking
- Moderated content system

## 🚧 Future Enhancements

- [ ] Email notifications for status updates
- [ ] Advanced analytics dashboard
- [ ] Mobile app development
- [ ] Integration with external APIs
- [ ] Multi-language support

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👤 Author

Nelson
- LinkedIn: www.linkedin.com/in/nelson-kinyungu-08ab87159
- Email: kinyungua@gmail.com

## 🙏 Acknowledgments

- Bootstrap team for the responsive framework
- Leaflet.js for excellent mapping capabilities
- OpenStreetMap for map data
- Icons from Bootstrap Icons
