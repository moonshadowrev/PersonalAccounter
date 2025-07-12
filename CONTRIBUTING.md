# Contributing to PersonalAccounter

We welcome contributions to PersonalAccounter! This guide will help you get started with contributing to the project.

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Getting Started](#getting-started)
3. [Development Setup](#development-setup)
4. [Contributing Process](#contributing-process)
5. [Coding Standards](#coding-standards)
6. [Testing Guidelines](#testing-guidelines)
7. [Documentation Guidelines](#documentation-guidelines)
8. [Commit Message Guidelines](#commit-message-guidelines)
9. [Pull Request Process](#pull-request-process)
10. [Issue Reporting](#issue-reporting)

## Code of Conduct

By participating in this project, you agree to abide by our Code of Conduct:

- **Be respectful** and inclusive of all contributors
- **Be collaborative** and help others learn and grow
- **Be patient** with questions and different skill levels
- **Be constructive** in feedback and criticism
- **Focus on the code**, not the person

### Unacceptable Behavior

- Harassment, discrimination, or offensive language
- Personal attacks or inflammatory comments
- Spam or off-topic discussions
- Sharing private information without permission

## Getting Started

### Prerequisites

Before contributing, ensure you have:

- **PHP 8.0+** installed
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Composer** for dependency management
- **Git** for version control
- **Node.js** (optional, for frontend development)

### First Contribution

If this is your first contribution:

1. **Look for issues** labeled `good first issue` or `help wanted`
2. **Read the documentation** thoroughly
3. **Start small** with bug fixes or documentation improvements
4. **Ask questions** if anything is unclear

## Development Setup

### 1. Fork and Clone

```bash
# Fork the repository on GitHub, then clone your fork
git clone https://github.com/your-username/PersonalAccounter.git
cd PersonalAccounter

# Add the original repository as upstream
git remote add upstream https://github.com/original-repo/PersonalAccounter.git
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env
```

### 3. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE personal_accounter_dev;
exit

# Update .env file with your database credentials
# Run migrations
php control migrate run

# Seed with test data
php control db seed
```

### 4. Development Environment

```bash
# Start development server
php -S localhost:8000 -t public/

# Or use your preferred web server (Apache/Nginx)
```

### 5. Verify Installation

Visit `http://localhost:8000` and ensure:
- ✅ Application loads without errors
- ✅ Database connection works
- ✅ You can log in with seeded user
- ✅ Basic functionality works

## Contributing Process

### 1. Create a Branch

```bash
# Update your fork
git fetch upstream
git checkout main
git merge upstream/main

# Create feature branch
git checkout -b feature/your-feature-name
# or
git checkout -b bugfix/issue-number-description
```

### 2. Make Changes

- **Keep changes focused** on a single feature or bug fix
- **Write tests** for new functionality
- **Update documentation** as needed
- **Follow coding standards** (see below)

### 3. Test Your Changes

```bash
# Run manual tests
php control test

# Check for syntax errors
find . -name "*.php" -exec php -l {} \;

# Test database migrations
php control migrate fresh
```

### 4. Commit and Push

```bash
# Stage your changes
git add .

# Commit with descriptive message
git commit -m "feat: add expense category filtering"

# Push to your fork
git push origin feature/your-feature-name
```

## Coding Standards

### PHP Standards

#### **PSR Standards**
- Follow **PSR-12** coding style
- Use **PSR-4** autoloading conventions
- Implement **PSR-3** logging interfaces where applicable

#### **Naming Conventions**

```php
// Classes: PascalCase
class ExpenseController extends Controller

// Methods and variables: camelCase
public function createExpense($expenseData)

// Constants: UPPER_SNAKE_CASE
const MAX_FILE_SIZE = 5242880;

// Database tables: snake_case
$table = 'expense_categories';
```

#### **Code Structure**

```php
<?php

// 1. Namespace declaration
namespace App\Controllers;

// 2. Use statements (grouped and sorted)
use App\Models\Expense;
use App\Services\Logger;
use Exception;

// 3. Class declaration
class ExpenseController extends Controller
{
    // 4. Properties (visibility order: public, protected, private)
    private $expenseModel;
    
    // 5. Constructor
    public function __construct($database)
    {
        $this->expenseModel = new Expense($database);
    }
    
    // 6. Methods (public first, then protected, then private)
    public function index()
    {
        // Implementation
    }
    
    private function validateExpenseData($data)
    {
        // Implementation
    }
}
```

#### **Documentation Standards**

```php
/**
 * Create a new expense record
 *
 * @param array $expenseData The expense data to create
 * @param int $userId The ID of the user creating the expense
 * @return int|false The created expense ID or false on failure
 * @throws InvalidArgumentException When expense data is invalid
 * @throws DatabaseException When database operation fails
 */
public function createExpense(array $expenseData, int $userId)
{
    // Implementation
}
```

### Database Standards

#### **Migration Guidelines**

```php
class CreateExpensesTable extends Migration
{
    public function getName()
    {
        return '010_create_expenses_table';
    }
    
    public function up()
    {
        $this->createTable('expenses', function($table) {
            // Primary key first
            $table->id();
            
            // Foreign keys
            $table->integer('user_id')->index();
            $table->integer('category_id')->nullable()->index();
            
            // Required fields
            $table->string('title');
            $table->decimal('amount', 12, 2);
            
            // Optional fields
            $table->text('description')->nullable();
            
            // Timestamps last
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
        });
    }
    
    public function down()
    {
        $this->dropTable('expenses');
    }
}
```

#### **Query Guidelines**

```php
// ✅ Good: Use prepared statements
$expenses = $this->db->select('expenses', '*', [
    'user_id' => $userId,
    'created_at[>=]' => $fromDate
]);

// ❌ Bad: String concatenation
$query = "SELECT * FROM expenses WHERE user_id = " . $userId;

// ✅ Good: Use meaningful variable names
$userExpenses = $this->expenseModel->getByUserId($userId);

// ❌ Bad: Unclear variable names
$data = $this->model->get($id);
```

### Frontend Standards

#### **JavaScript Guidelines**

```javascript
// Use ES6+ features
const createExpenseChart = (data) => {
    const canvas = document.getElementById('expense-chart');
    const ctx = canvas.getContext('2d');
    
    return new Chart(ctx, {
        type: 'pie',
        data: data,
        options: {
            responsive: true
        }
    });
};

// Use meaningful function names
function validateExpenseForm(formData) {
    const errors = [];
    
    if (!formData.title) {
        errors.push('Title is required');
    }
    
    if (formData.amount <= 0) {
        errors.push('Amount must be positive');
    }
    
    return errors;
}
```

#### **CSS Guidelines**

```css
/* Use BEM naming convention */
.expense-form {
    /* Block */
}

.expense-form__input {
    /* Element */
}

.expense-form__input--error {
    /* Modifier */
}

/* Use consistent spacing */
.form-group {
    margin-bottom: 1rem;
}

/* Use CSS custom properties for themes */
:root {
    --primary-color: #3B82F6;
    --success-color: #10B981;
    --error-color: #EF4444;
}
```

## Testing Guidelines

### Manual Testing

Before submitting:

1. **Test core functionality**
   - Create, read, update, delete operations
   - User authentication and authorization
   - API endpoints

2. **Test edge cases**
   - Invalid input data
   - Large datasets
   - Concurrent operations

3. **Cross-browser testing**
   - Chrome, Firefox, Safari
   - Mobile responsiveness

### Automated Testing

#### **Unit Tests** (Coming Soon)

```php
class ExpenseTest extends TestCase
{
    public function testCreateExpense()
    {
        $expense = new Expense($this->database);
        
        $data = [
            'title' => 'Test Expense',
            'amount' => 100.50,
            'user_id' => 1
        ];
        
        $result = $expense->create($data);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }
}
```

#### **API Tests**

```bash
# Test API endpoints
curl -X GET \
  'http://localhost:8000/api/v1/expenses' \
  -H 'X-API-Key: your-test-key' \
  -H 'Content-Type: application/json'
```

## Documentation Guidelines

### Code Documentation

- **Document all public methods** with PHPDoc
- **Explain complex logic** with inline comments
- **Include examples** for non-obvious usage
- **Keep documentation up-to-date** with code changes

### Feature Documentation

When adding new features:

1. **Update the Feature Wiki** (`docs/FEATURES.md`)
2. **Add API documentation** if creating new endpoints
3. **Update README** if changing installation or usage
4. **Add examples** showing how to use the feature

### API Documentation

```php
/**
 * @OA\Post(
 *     path="/api/v1/expenses",
 *     summary="Create a new expense",
 *     tags={"Expenses"},
 *     security={{"ApiKeyAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title", "amount", "user_id"},
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="amount", type="number", format="float"),
 *             @OA\Property(property="user_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Expense created successfully"
 *     )
 * )
 */
```

## Commit Message Guidelines

### Format

```
<type>(<scope>): <description>

<body>

<footer>
```

### Types

- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, etc.)
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **chore**: Maintenance tasks

### Examples

```bash
# Good commit messages
git commit -m "feat(expenses): add category filtering to expense list"
git commit -m "fix(api): resolve rate limiting bug for high-volume users"
git commit -m "docs(readme): update installation instructions for PHP 8.1"

# Detailed commit with body
git commit -m "feat(dashboard): add real-time expense analytics

- Implement WebSocket connection for live updates
- Add Chart.js integration for visual analytics
- Include date range filtering for analytics

Closes #123"
```

## Pull Request Process

### Before Submitting

- [ ] **Code follows** style guidelines
- [ ] **Tests pass** (manual and automated)
- [ ] **Documentation updated** as needed
- [ ] **Commit messages** follow guidelines
- [ ] **Branch is up-to-date** with main

### PR Template

```markdown
## Description
Brief description of changes made.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Documentation update
- [ ] Refactoring

## Testing
- [ ] Manual testing completed
- [ ] Automated tests pass
- [ ] Cross-browser testing (if applicable)

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or documented)

## Screenshots (if applicable)
Add screenshots for UI changes.

## Related Issues
Closes #123
```

### Review Process

1. **Automated checks** must pass
2. **At least one approval** from maintainer required
3. **Address feedback** promptly and respectfully
4. **Maintainer merges** after approval

### After Merge

- **Delete feature branch** from your fork
- **Update your local main** branch
- **Thank reviewers** for their time

## Issue Reporting

### Bug Reports

Use this template for bug reports:

```markdown
**Bug Description**
Clear description of what the bug is.

**Steps to Reproduce**
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected Behavior**
What you expected to happen.

**Actual Behavior**
What actually happened.

**Environment**
- OS: [e.g. Ubuntu 20.04]
- PHP Version: [e.g. 8.1]
- Browser: [e.g. Chrome 96]

**Additional Context**
Add any other context about the problem.
```

### Feature Requests

```markdown
**Feature Description**
Clear description of the feature you'd like to see.

**Problem Statement**
What problem does this feature solve?

**Proposed Solution**
Describe your preferred solution.

**Alternatives Considered**
Alternative solutions you've considered.

**Additional Context**
Any other context or screenshots.
```

## Development Tips

### Debugging

```php
// Use AppLogger for debugging
AppLogger::debug('Expense data', ['data' => $expenseData]);

// Environment-specific debugging
if (Config::get('debug')) {
    var_dump($variable);
}
```

### Performance

- **Use database indexes** for frequently queried columns
- **Implement pagination** for large datasets
- **Cache expensive operations** when appropriate
- **Optimize database queries** to avoid N+1 problems

### Security

- **Validate all input** data
- **Use prepared statements** for database queries
- **Implement proper authentication** checks
- **Follow OWASP guidelines** for web security

## Getting Help

### Resources

- **Documentation**: Check `docs/` directory
- **API Docs**: Available at `/api/docs/ui` (development mode)
- **Examples**: Look at existing code for patterns

### Communication

- **GitHub Issues**: For bugs and feature requests
- **GitHub Discussions**: For questions and general discussion
- **Email**: maintainers@your-domain.com for urgent matters

### Response Times

- **Issues**: We aim to respond within 48 hours
- **Pull Requests**: Reviews typically within 72 hours
- **Security Issues**: Response within 24 hours

## Recognition

We appreciate all contributions! Contributors are recognized in:

- **CHANGELOG.md**: Major contributions listed in releases
- **README.md**: Top contributors section
- **GitHub**: Automatic contribution tracking

Thank you for contributing to PersonalAccounter! 🎉

---

**Questions?** Don't hesitate to ask! We're here to help you contribute successfully. 