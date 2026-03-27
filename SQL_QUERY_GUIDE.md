# Custom Quiz SQL Query Configuration Guide

## Overview
This plugin allows you to configure custom SQL queries for quiz modules that are not automatically supported. Standard `quiz` and `adaptivequiz` modules work automatically without any configuration.

## Format
Each line represents one quiz module:
```
modname|SELECT ... FROM ... WHERE ... :userid ... :quizid ... AS attempt_status ... AS attempt_number
```

## Requirements

### 1. **Placeholders (REQUIRED)**
Your query MUST include these placeholders:
- `:userid` - Will be replaced with the actual user ID
- `:quizid` - Will be replaced with the actual quiz instance ID

### 2. **Aliases (REQUIRED)**
Your query MUST return these aliases:
- `AS attempt_status` - The status of the attempt (e.g., 'finished', 'inprogress', 'abandoned')
- `AS attempt_number` - The attempt number (integer or count)

### 3. **Best Practices**
- Include `id` column in SELECT for record identification
- Use `ORDER BY timemodified DESC` or similar to get the latest attempt
- Use `LIMIT 1` to return only the most recent attempt
- Use Moodle table syntax: `{tablename}` (curly braces, no `mdl_` prefix)

## Examples

### Example 1: Simple Custom Quiz
```
customquiz|SELECT id, status AS attempt_status, attemptnum AS attempt_number FROM {customquiz_attempts} WHERE user_id = :userid AND quiz_id = :quizid ORDER BY timemodified DESC LIMIT 1
```

### Example 2: Different Column Names
```
examquiz|SELECT id, state AS attempt_status, trynum AS attempt_number FROM {examquiz_tries} WHERE uid = :userid AND examid = :quizid ORDER BY created DESC LIMIT 1
```

### Example 3: Using Subquery to Count Attempts
If your table doesn't have an attempt number column, use a subquery:
```
myquiz|SELECT id, attempt_state AS attempt_status, (SELECT COUNT(*) FROM {myquiz_attempts} WHERE userid = :userid AND quizid = :quizid) AS attempt_number FROM {myquiz_attempts} WHERE userid = :userid AND quizid = :quizid ORDER BY timemodified DESC LIMIT 1
```

### Example 4: Complex Status Logic
```
advancedquiz|SELECT id, CASE WHEN timefinish > 0 THEN 'finished' ELSE 'inprogress' END AS attempt_status, attempt AS attempt_number FROM {advancedquiz_attempts} WHERE userid = :userid AND quiz = :quizid ORDER BY timestart DESC LIMIT 1
```

### Example 5: Multiple Quiz Types Configuration
```
# Custom quiz module
customquiz|SELECT id, status AS attempt_status, attemptnum AS attempt_number FROM {customquiz_attempts} WHERE user_id = :userid AND quiz_id = :quizid ORDER BY timemodified DESC LIMIT 1

# Exam quiz with different column names
examquiz|SELECT id, state AS attempt_status, trynum AS attempt_number FROM {examquiz_tries} WHERE uid = :userid AND examid = :quizid ORDER BY created DESC LIMIT 1

# Advanced quiz with counting
advquiz|SELECT id, status AS attempt_status, (SELECT COUNT(*) FROM {advquiz_attempts} WHERE userid = :userid AND quizid = :quizid) AS attempt_number FROM {advquiz_attempts} WHERE userid = :userid AND quizid = :quizid ORDER BY timemodified DESC LIMIT 1
```

## Step-by-Step: Building Your Query

### Step 1: Find Your Table
```sql
SHOW TABLES LIKE '%attempt%';
SHOW TABLES LIKE '%quiz%';
```

### Step 2: Examine Table Structure
```sql
DESCRIBE mdl_yourquiz_attempts;
```

### Step 3: Test Basic Query
```sql
SELECT * FROM mdl_yourquiz_attempts WHERE userid = 2 LIMIT 1;
```

### Step 4: Identify Columns
Look for columns that contain:
- **User ID**: Usually `userid`, `user_id`, `uid`, `user`
- **Quiz ID**: Usually `quiz`, `quizid`, `instance`, `quiz_id`, `examid`
- **Status**: Usually `state`, `status`, `attemptstate`, `attempt_state`
- **Attempt Number**: Usually `attempt`, `attemptnum`, `attemptnumber`, `trynum`
- **Time**: Usually `timemodified`, `timecreated`, `created`, `timestart`

### Step 5: Build Your Query
```sql
SELECT 
    id,
    [status_column] AS attempt_status,
    [attempt_column] AS attempt_number
FROM {your_table}
WHERE [userid_column] = :userid AND [quizid_column] = :quizid
ORDER BY [time_column] DESC
LIMIT 1
```

### Step 6: Format for Configuration
```
modname|SELECT id, status AS attempt_status, attemptnum AS attempt_number FROM {yourtable_attempts} WHERE userid = :userid AND quizid = :quizid ORDER BY timemodified DESC LIMIT 1
```

## Common Issues

### Issue 1: No Attempt Number Column
**Solution**: Use a subquery to count attempts
```sql
SELECT id, state AS attempt_status, 
    (SELECT COUNT(*) FROM {table} WHERE userid = :userid AND quizid = :quizid) AS attempt_number
FROM {table} 
WHERE userid = :userid AND quizid = :quizid 
ORDER BY timemodified DESC 
LIMIT 1
```

### Issue 2: Status is Numeric (0, 1, 2)
**Solution**: Use CASE statement to convert to text
```sql
SELECT id, 
    CASE status 
        WHEN 0 THEN 'inprogress' 
        WHEN 1 THEN 'finished' 
        ELSE 'abandoned' 
    END AS attempt_status,
    attempt AS attempt_number
FROM {table}
WHERE userid = :userid AND quizid = :quizid
```

### Issue 3: Multiple Attempts in Progress
**Solution**: Add additional WHERE conditions
```sql
SELECT id, state AS attempt_status, attempt AS attempt_number
FROM {table}
WHERE userid = :userid 
    AND quizid = :quizid 
    AND state != 'abandoned'
ORDER BY timemodified DESC
LIMIT 1
```

## Testing Your Configuration

1. **Add your query** to the configuration textarea
2. **Clear Moodle cache**: Site Administration → Development → Purge all caches
3. **Test with a quiz attempt**: Have a user attempt the quiz
4. **Check debugging**: Enable debugging to see any SQL errors
5. **Verify output**: Check that attempt_status and attempt_number are returned correctly

## Comments

You can add comments to your configuration:
```
# This is a comment - lines starting with # are ignored
// This is also a comment - lines starting with // are ignored

customquiz|SELECT id, state AS attempt_status, attempt AS attempt_number FROM {customquiz_attempts} WHERE userid = :userid AND quizid = :quizid ORDER BY timemodified DESC LIMIT 1
```

## Security Notes

- All queries use **prepared statements** with bound parameters - SQL injection is prevented
- Only SELECT queries should be used
- The `:userid` and `:quizid` parameters are automatically sanitized
- Queries run with the same permissions as the Moodle database user

## Troubleshooting

### Query Not Working?
1. Check Moodle debug logs
2. Verify table name exists: `SHOW TABLES LIKE '%yourmodule%';`
3. Verify columns exist: `DESCRIBE mdl_yourtable;`
4. Test query manually with real values
5. Ensure aliases are spelled correctly: `attempt_status` and `attempt_number`
6. Ensure placeholders are used: `:userid` and `:quizid`

### No Attempts Returned?
1. Check WHERE clause matches your table structure
2. Verify data exists: `SELECT * FROM mdl_yourtable LIMIT 1;`
3. Check ORDER BY column exists
4. Verify quiz_id column matches your instance ID

### Error Messages?
- **"Custom query must return attempt_status and attempt_number aliases"**: Add the required aliases
- **"Custom query execution failed"**: Check SQL syntax and table/column names
- **No error but null returned**: Check WHERE conditions and verify data exists
