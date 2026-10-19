# Innovation Learning Center LMS

Innovation Learning Center is a school management system built with Laravel 12. Teachers take attendance
and enter scores in a live spreadsheet-style gradebook with weighted grade categories. Administrators track
grades, attendance and at-risk students on a single dashboard, manage classes, rosters and staff, and print
student report cards as PDFs.

## Run it

You need **PHP 8.2+** and **[Composer](https://getcomposer.org)**. Then:

```bash
git clone https://github.com/reshard-porter/Laravel-LMS.git
cd Laravel-LMS
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite        # Windows: type nul > database\database.sqlite
php artisan migrate --seed            # creates the tables and a demo school
php artisan serve
```

Open **http://127.0.0.1:8000** and sign in:

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@lms.test` | `password` |
| Teacher (AI and Database Systems) | `reshard@lms.test` | `password` |
| Other teachers | `randy@`, `rochelle@`, `marcia@`, `devon@lms.test` | `password` |

**Requirements:** PHP 8.2 or newer, with the `pdo_sqlite`, `mbstring`, `openssl` and `fileinfo` extensions
(these are on by default with Homebrew PHP on macOS and with Laravel Herd).

## What's in it

**Dashboard.** School-wide numbers (students, classes, average grade, attendance over the last 30 days,
overdue scores), class averages against the at-risk line, the grade distribution, weekly attendance,
students below 70% in any class, and everything due in the next two weeks. Teachers see the same view
for their own classes. Each chart can also be shown as a table.

**Classes.** Code, teacher, schedule, room, term and seat count. Each class has:
- a **roster** where you enrol students (capacity is enforced) or remove them,
- **assignments** in four categories (homework, quizzes, projects, exams) with points and due dates,
- **grade weights**, either the school defaults or custom weights for that class (they must add up to 100%).

**Gradebook.** A grid of students by assignments.
- Type points, `EX` to excuse a student, or leave a cell blank if it isn't graded yet.
- Final grades, assignment averages and the class average update as you type.
- Enter and the arrow keys move between cells. Ctrl+S (or ⌘S) saves.
- Out-of-range values are flagged before saving. If any value is invalid, nothing is saved.
- Past-due cells with no score are hatched so missing work stands out.
- **Export CSV** downloads the gradebook as a spreadsheet that opens in Excel.

**Attendance.** Pick a date, mark each student present, late, absent or excused, and save. Recent class
days show the attendance rate. Late counts as attended; excused absences aren't counted either way.

**Students.** Search and filter by class or status. Each profile shows every class with its grade,
attendance and missing work, plus a breakdown of each assignment. **Report card** opens a printable page
(print it or save it as a PDF).

**Teachers.** Staff records. Setting a password for a teacher gives them a sign-in.

**Roles.**
- *Administrators* manage everything.
- *Teachers* manage the roster, assignments, gradebook, attendance and settings of their own classes,
  and can view students and staff. They can't open another teacher's gradebook or add or delete people
  or classes.

## How grades are calculated

1. Each category's percentage = points earned ÷ points possible, counting only graded, non-excused work.
2. The final grade is the weighted average of the categories, using the class's weights.
   Categories with no graded work yet are left out, so an empty "Exams" column doesn't pull a grade down.
3. Letters: A 90+, B 80–89, C 70–79, D 60–69, F below 60. The thresholds, default weights and at-risk
   line are in `config/lms.php`.

## Useful commands

```bash
php artisan migrate:fresh --seed   # reset to a fresh demo school
php vendor/bin/phpunit             # run the 29 automated tests
composer install                   # only needed if you delete vendor/
```

The demo school is generated relative to today's date, so after a reset it always looks mid-term: past
work is graded, one assignment is half-graded, and some work is still upcoming.

## Settings (`.env`)

```
LMS_SCHOOL_NAME="Innovation Learning Center"
LMS_CURRENT_TERM="Fall 2026"
```

### Using MySQL instead of SQLite

Create an empty database (for example `school`), then set the following in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school
DB_USERNAME=root
DB_PASSWORD=
```

Then run `php artisan migrate --seed`.

## Where things live

| Path | What it is |
|---|---|
| `app/Services/GradeCalculator.php` | The grading maths (no database, unit tested) |
| `app/Services/Gradebook.php` | Loads a class's students, assignments and scores and computes results |
| `app/Http/Controllers/` | One controller per area: dashboard, classes, roster, assignments, gradebook, attendance, students, teachers |
| `database/migrations/` | Tables: users, teachers, students, classrooms, enrolments, assignments, grades, attendance |
| `database/seeders/DemoSchoolSeeder.php` | The demo school |
| `resources/views/` | Blade templates; `public/css/app.css` is the stylesheet |
| `tests/` | Unit tests for grading, feature tests for access, the gradebook, classes and attendance |

Links from the original version (`/DisplayTeachers`, `/InsertStudent` and so on) redirect to the new pages.
