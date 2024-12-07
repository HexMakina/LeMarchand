[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HexMakina/LeMarchand/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/HexMakina/LeMarchand/?branch=main)
<img src="https://img.shields.io/badge/PSR-4-brightgreen" alt="PSR-4 Compliant" />
<img src="https://img.shields.io/badge/PSR-11-brightgreen" alt="PSR-11 Compliant" />
<img src="https://img.shields.io/badge/PSR-12-brightgreen" alt="PSR-12 Compliant" />
<img src="https://img.shields.io/badge/PHP-7.0-brightgreen" alt="PHP 7.0 Required" />


# **LeMarchand**

A **PSR-11 compliant service container** built for the kadro framework. It combines features of a **Service Locator** and **Dependency Injection Container**, designed to manage configurations, services, and dependencies efficiently. LeMarchand is complemented by two core components:
- **Solver**: Handles dynamic dependency resolution.
- **Factory**: Manages object instantiation and caching.

---

## **Features**
- **PSR-11 Compliant**: Implements the standard container interface, providing `has` and `get` methods.
- **Dynamic Dependency Resolution**: Supports complex resolution strategies via the `Solver`.
- **Efficient Instance Management**: Includes a `Factory` to cache instances and manage singletons.
- **Flexible Configuration**: Easily load settings, register services, and resolve dependencies.
- **Extensible**: Designed for use in applications that require advanced dependency management.

---

## **Installation**
```bash
composer require hexmakina/le-marchand
```

---

## **Usage**

### **Instantiate and Load Configuration**
You can initialize the container with a configuration array:

```php
$settings = [  
  'app' => [
    'name' => 'KORAL',
    'production_host' => 'engine.hexmakina.be',
    'session_start_options' => ['session_name' => 'koral-alias'],
    'time_window_start' => '-3 months',
    'time_window_stop' => '+1 month',
  ],
  'controller_namespaces' => [
    'App\\Controllers\\',
    'HexMakina\\koral\\Controllers\\',
    'HexMakina\\kadro\\Controllers\\'
  ]
];

$box = new LeMarchand($settings);
```

### **Access Configuration**
Settings are stored under the `settings` key:
```php
$box->has('settings'); // returns true
$box->has('settings.app.name'); // returns true
$box->get('settings.app.name'); // returns 'KORAL'
```

### **Register Additional Services**
You can register additional services manually:
```php
$box->register('HexMakina\Crudites\ConnectionInterface', $connection);
```
---

## **Core Components**

### **1. LeMarchand**
LeMarchand is the main container class. It:
- Implements the PSR-11 `ContainerInterface`.
- Manages a centralized collection of services and configurations.
- Provides mechanisms for retrieving and checking for services (`get` and `has`).

#### Key Features:
- Singleton Pattern: Ensures only one instance of the container exists.
- Configuration Management: Supports hierarchical settings (e.g., `settings.database.host`).
- Integration with Solver and Factory for advanced dependency resolution and instantiation.

---

### **2. Solver**
The `Solver` class extends LeMarchand’s capabilities by dynamically resolving dependencies using multiple strategies:
- **Settings Probe**: Resolves hierarchical keys (e.g., `settings.app.name`) from the configuration array.
- **Class Probe**: Instantiates a class if it exists.
- **Interface Probe**: Maps interfaces to implementations via a wiring configuration.
- **Cascade Probe**: Dynamically resolves classes using a namespace cascade for patterns like MVC.

#### Example:
```php
// Resolve a setting
$solver->probeSettings('settings.app.name'); // Returns 'KORAL'

// Resolve a class
$solver->probeClasses(App\Controllers\UserController::class);

// Resolve an interface
$solver->probeInterface(App\Contracts\LoggerInterface::class);

// Resolve dynamically using namespaces
$solver->probeCascade('Controllers\UserController');
```

---

### **3. Factory**
The `Factory` class handles the creation and caching of class instances. It:
- Dynamically resolves constructor dependencies using the container.
- Caches instances to prevent redundant object creation.
- Handles singleton creation for classes with private constructors.

#### Example:
```php
$factory = new Factory($box);

// Retrieve a cached instance or create a new one
$instance = $factory->serve(App\Services\EmailService::class);

// Build a new instance of a class
$newInstance = $factory->build(App\Models\User::class);

// Create a singleton
$singleton = $factory->buildSingleton(new \ReflectionClass(App\Services\Singleton::class), ['getInstance', []]);
```

---

## **Error Handling**
LeMarchand adheres to PSR-11 standards for error handling:
- **`Psr\Container\NotFoundExceptionInterface`**: Thrown when a service or configuration is not found.
- **`Psr\Container\ContainerExceptionInterface`**: Thrown when there’s an issue resolving a service.

---

## **Advanced Usage**

### Dynamic Dependency Resolution with Solver
LeMarchand supports complex resolution scenarios using the `Solver`:
```php
// Resolve a service with constructor dependencies
$controller = $box->get(App\Controllers\UserController::class);
```

### Singleton Management with Factory
The `Factory` simplifies singleton management:
```php
$singleton = $factory->buildSingleton(
    new \ReflectionClass(App\Services\Logger::class),
    ['getInstance', []]
);
```

---

## **Contributing**
Feel free to submit issues or pull requests. Contributions are welcome!

---

## **Lore & Homage**
The **LeMarchand** box, also known as the **Lament Configuration**, was introduced in *The Hellbound Heart* novella and later appeared in the *Hellraiser* film series. This project pays homage to its mystique by solving the "lamentations" of dependency management.
