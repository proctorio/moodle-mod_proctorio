# Proctorio Moodle Plugin

If you want to use the Proctorio extension in your Moodle but you have a custom theme or you are using an adaptive quiz that changes the appearance of your Moodle, this plugin can help you to insert the selectors that are different from default Moodle selectors, and enable using proctorio in your Moodle instance.

## Features

### Core Features
- **Candidate Selectors Configuration**: Add and edit CSS selectors for candidate-side quiz interface elements
- **Professor Selectors Configuration**: Add and edit CSS selectors for instructor-side quiz management interface
- **Custom Quiz Type Support**: Configure custom SQL queries for non-standard quiz modules
- **RESTful API Endpoints**: Access quiz attempt data, course rosters, and selector configurations via API

### Advanced Features
- **Custom SQL Query Engine**: Write flexible SQL queries for custom quiz types with automatic parameter binding
- **Multiple Quiz Module Support**: Built-in support for standard `quiz` and `adaptivequiz` modules, plus unlimited custom modules
- **Real-time Selector Fetching**: AJAX endpoints for dynamic selector retrieval
- **Version Information API**: Retrieve plugin and Moodle version details programmatically

## Requirements

- **Moodle version**: Moodle 3.9 or later (tested up to Moodle 4.x)
- **PHP version**: 7.4 or higher
- **Database**: MySQL 5.7+ or PostgreSQL 9.6+
- **Permissions**: Site administrator access for configuration

## Installation

1. Download the plugin
2. Sign in as an Admin in the Moodle
3. Go to the Site administration page
4. Find plugins section
5. Click on "Install plugins"
6. Drag the zip file to the drag & drop component in the Moodle
7. Click on "Install plugin from the ZIP file"
8. Complete installation

## Configuration

### Basic Setup

1. Sign in as an Admin in the Moodle
2. Go to Site administration page > Plugins > Local plugins > Proctorio Moodle Selectors
3. Fill the form with the appropriate selectors

### Candidate Selectors

Configure CSS selectors for the student quiz interface. Default selectors include:
- **Quiz Access Code**: Password input field (`#id_quizpassword`)
- **Quiz Info**: Quiz information display (`.quizinfo`)
- **Quiz Attempt**: Attempt history section (`.quizattempt`)
- **Page Elements**: Navigation, breadcrumbs, main content area
- **Quiz Time Left**: Timer display (`#quiz-time-left`)
- **Submit Buttons**: Quiz submission controls (`.submitbtns`)
- **URL Paths**: Quiz landing page, attempt page, review page paths

### Professor Selectors

Configure CSS selectors for the instructor interface. Default selectors include:
- **Quiz Reports**: Report page elements (`.page-mod-quiz-report`)
- **General Table**: Quiz data tables (`.generaltable`)
- **Quiz Overrides**: Override management section (`#quizoverrides`)
- **Dropdown Items**: Navigation dropdowns (`.dropdown-item`)
- **Quiz Management**: Quiz settings and configuration elements

### Custom Quiz SQL Queries (Advanced)

For custom quiz modules that aren't automatically supported, configure SQL queries to fetch quiz attempt data.

**Standard modules** (`quiz` and `adaptivequiz`) work automatically without configuration.

#### Query Configuration

**Location**: Site Administration → Plugins → Local plugins → Proctorio Moodle Selectors → Custom Quiz SQL Queries

**Format**:
```
modname|SELECT id, status_col AS attempt_status, attempt_col AS attempt_number FROM {table} WHERE userid_col = :userid AND quizid_col = :quizid ORDER BY timemodified DESC LIMIT 1
```

#### Requirements
- Must use `:userid` and `:quizid` placeholders (can be repeated, including in subqueries)
- Must return aliases: `AS attempt_status` and `AS attempt_number`
- Use Moodle table syntax: `{tablename}` (curly braces, no `mdl_` prefix)
- Include `ORDER BY` and `LIMIT 1` for optimal performance

#### Examples

**Simple custom quiz**:
```
customquiz|SELECT id, status AS attempt_status, attempt AS attempt_number FROM {customquiz_attempts} WHERE userid = :userid AND quizid = :quizid ORDER BY timemodified DESC LIMIT 1
```

**With subquery to count attempts**:
```
myquiz|SELECT id, state AS attempt_status, (SELECT COUNT(*) FROM {myquiz_attempts} WHERE userid = :userid AND quizid = :quizid) AS attempt_number FROM {myquiz_attempts} WHERE userid = :userid AND quizid = :quizid ORDER BY timemodified DESC LIMIT 1
```

**Different column names**:
```
examquiz|SELECT id, state AS attempt_status, trynum AS attempt_number FROM {examquiz_tries} WHERE uid = :userid AND examid = :quizid ORDER BY created DESC LIMIT 1
```

**Multiple quiz types** (one per line):
```
# Custom quiz type 1
customquiz|SELECT id, status AS attempt_status, attempt AS attempt_number FROM {customquiz_attempts} WHERE userid = :userid AND quizid = :quizid ORDER BY timemodified DESC LIMIT 1

# Exam quiz with different schema
examquiz|SELECT id, state AS attempt_status, trynum AS attempt_number FROM {examquiz_tries} WHERE uid = :userid AND examid = :quizid ORDER BY created DESC LIMIT 1
```

#### Finding Database Information

Use these SQL queries to identify your table structure:

```sql
-- Find quiz attempt tables
SHOW TABLES LIKE '%attempt%';

-- View table structure
DESCRIBE mdl_yourquiz_attempts;

-- Test with sample data
SELECT * FROM mdl_yourquiz_attempts WHERE userid = 2 LIMIT 1;
```

#### Additional Documentation
- `docs/SQL_QUERY_GUIDE.md` - Comprehensive guide with detailed examples
- `docs/QUERY_TEMPLATES.txt` - Ready-to-use templates for common scenarios
- `docs/QUICKSTART.txt` - Quick reference guide

## API Endpoints

The plugin exposes its API as Moodle External Services (`db/services.php` +
`classes/external/*`), callable via Moodle's core AJAX dispatcher
(`lib/ajax/service.php`) using the caller's existing session (cookies) and sesskey -
no separate web service token is required. Each function validates its own
parameters and capability inside `execute()`.

### 1. Quiz Attempt Information

**Function**: `local_proctorio_get_attempt_info`

**Capability**: `local/proctorio:viewattemptdata` (module context)

**Parameters**:
- `cmid` (required, integer) - Course module ID of the quiz-like activity

**Response**:
```json
{
  "found": true,
  "attempt_status": "finished",
  "attempt_number": "3"
}
```

### 2. Course Roster

**Function**: `local_proctorio_get_course_roster`

**Capability**: `local/proctorio:viewroster` (course context)

**Parameters**:
- `courseid` (required, integer) - Course ID

**Response**:
```json
[
  {
    "id": 123,
    "fullname": "John Doe",
    "email": "john.doe@example.com"
  }
]
```

### 3. CSS Selectors

**Function**: `local_proctorio_get_selectors`

**Capability**: `local/proctorio:viewselectors` (system context)

**Parameters**:
- `type` (required, string) - `"student"` or `"professor"`

**Response**: JSON-encoded array of the configured selectors for that audience.

### 4. Plugin Details

**Function**: `local_proctorio_get_plugin_details`

**Capability**: `local/proctorio:viewselectors` (system context)

**Response**:
```json
{
  "pluginversion": "2.4.0",
  "moodleversion": "4.1.0"
}
```

## Usage Examples

### JavaScript Integration

**Fetch quiz attempt information** (via Moodle's `core/ajax` module):
```javascript
import Ajax from 'core/ajax';

const [attempt] = Ajax.call([
  { methodname: 'local_proctorio_get_attempt_info', args: { cmid: 123 } }
]);

attempt.then(data => {
  if (data.found) {
    console.log('Attempt status:', data.attempt_status);
    console.log('Attempt number:', data.attempt_number);
  }
});
```

**Fetch course roster** (e.g. from outside an AMD module, against
`lib/ajax/service.php` directly, using the page's own session and sesskey):
```javascript
fetch(`${M.cfg.wwwroot}/lib/ajax/service.php?sesskey=${M.cfg.sesskey}&info=local_proctorio_get_course_roster`, {
  method: 'POST',
  credentials: 'same-origin',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify([{ index: 0, methodname: 'local_proctorio_get_course_roster', args: { courseid: 456 } }])
})
  .then(response => response.json())
  .then(([{ data: roster }]) => {
    roster.forEach(user => console.log(`${user.fullname} (${user.email})`));
  });
```

## Technical Details

### Architecture
- **Plugin Type**: Local plugin
- **Component**: `local_proctorio`
- **Maturity**: MATURITY_STABLE
- **Current Version**: 2.4.0

### Key Components
- `attempt_fetcher.php` - Class for retrieving quiz attempt data with custom SQL support
- `lib.php` - Helper functions for selector management and roster/attempt lookups
- `settings.php` - Admin configuration interface
- `classes/external/*` - External Service functions (the plugin's API surface)
- `db/services.php` - External Service registration

### Security Features
- **Parameter Binding**: All SQL queries use prepared statements with bound parameters
- **Login Requirements**: All external functions require an authenticated session
- **Capability Checks**: Each `execute()` validates context and requires the
  capability appropriate to the data it returns (module, course, or system level)
- **Type Safety**: Strict parameter type checking via `external_value`/`PARAM_*`
- **SQL Injection Protection**: Automatic placeholder numbering and PDO-based execution
- **Access Control**: Course/module permission checks for roster and attempt endpoints

### Performance Considerations
- Queries use `LIMIT 1` for optimal performance
- Indexed columns recommended for userid, quizid, and timemodified
- Placeholder processing adds minimal overhead (~microseconds)
- Caching recommended for frequently accessed selectors

## Troubleshooting

### Custom Quiz Queries Not Working

1. **Check Moodle debug logs**: Enable debugging in Moodle settings
2. **Verify table structure**: Use `DESCRIBE mdl_yourtable;` to confirm columns
3. **Test query manually**: Run query in phpMyAdmin with real values
4. **Verify placeholders**: Ensure `:userid` and `:quizid` are present
5. **Check aliases**: Confirm `AS attempt_status` and `AS attempt_number` exist
6. **Clear cache**: Purge all caches after configuration changes

### API Endpoints Returning Errors

- **404 errors**: User not logged in or not using AJAX
- **405 errors**: Wrong HTTP method (must be GET)
- **400 errors**: Missing required parameters
- **403 errors**: Insufficient permissions for the requested resource

### Selectors Not Applied

1. Verify selectors are correctly saved in plugin settings
2. Check browser developer console for JavaScript errors
3. Ensure Proctorio extension is installed and active
4. Clear Moodle cache and browser cache
5. Verify selectors match your theme's HTML structure

## Support

For additional help:
- Review the comprehensive guides in the plugin directory
- Check Moodle logs for detailed error messages
- Verify database permissions and structure
- Ensure compatibility with your Moodle version

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

**Copyright**: 2025 Proctorio <support@proctorio.com>  
**License**: GNU GPL v3 or later
