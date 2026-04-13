/* ==========================================================
   PORTFOLIO GENERATOR - PUBLIC TEST PORTFOLIO SEED
   Purpose: insert a complete public freelancer portfolio
   for testing guest search, public view, preview, skills,
   projects, and contact sections.
========================================================== */

USE portfolio_gen;

START TRANSACTION;

DELETE sl
FROM social_links sl
INNER JOIN portfolios p ON p.id = sl.portfolio_id
WHERE p.slug = 'nassim-charaabi-freelance-portfolio'
  OR p.email = 'nassim.charaabi@example.com';

DELETE pr
FROM projects pr
INNER JOIN portfolios p ON p.id = pr.portfolio_id
WHERE p.slug = 'nassim-charaabi-freelance-portfolio'
  OR p.email = 'nassim.charaabi@example.com';

DELETE s
FROM skills s
INNER JOIN portfolios p ON p.id = s.portfolio_id
WHERE p.slug = 'nassim-charaabi-freelance-portfolio'
  OR p.email = 'nassim.charaabi@example.com';

DELETE FROM portfolios
WHERE slug = 'nassim-charaabi-freelance-portfolio'
  OR email = 'nassim.charaabi@example.com';

DELETE FROM users
WHERE email = 'nassim.charaabi@example.com';

INSERT INTO users (first_name, last_name, email, password)
VALUES (
  'Nassim',
  'Charaabi',
  'nassim.charaabi@example.com',
  '$2y$10$W2W8s5v0R3n7h3D0lQq4hO7Qe8S6kQw8Y6VY4iXqJ8Rkq7m7QnS7e'
);

SET @user_id := LAST_INSERT_ID();

INSERT INTO portfolios (
  user_id,
  portfolio_title,
  slug,
  is_public,
  theme_name,
  job_title,
  profile_photo_url,
  location,
  website_url,
  bio_short,
  bio_long,
  years_exp,
  phone,
  email
)
VALUES (
  @user_id,
  'Nassim Charaabi - Freelance Portfolio',
  'nassim-charaabi-freelance-portfolio',
  1,
  'aurora',
  'Full-Stack Freelancer | Web Developer',
  'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80',
  'Casablanca, Morocco',
  'https://nassimcharaabi.dev',
  'Freelance developer focused on modern web apps, clean UI, and strong user experiences.',
  'I build professional portfolio websites, business landing pages, and full-stack web applications for clients who need a fast, polished, and reliable online presence. My workflow combines design thinking, performance, and maintainable code so every project is simple to grow over time.',
  '4+ years',
  '+212 600 123 456',
  'nassim.charaabi@example.com'
);

SET @portfolio_id := LAST_INSERT_ID();

INSERT INTO skills (portfolio_id, skill_name, proficiency_level) VALUES
(@portfolio_id, 'HTML5', 95),
(@portfolio_id, 'CSS3', 92),
(@portfolio_id, 'JavaScript', 90),
(@portfolio_id, 'PHP', 88),
(@portfolio_id, 'MySQL', 86),
(@portfolio_id, 'Bootstrap', 84),
(@portfolio_id, 'Responsive Design', 96),
(@portfolio_id, 'API Integration', 82);

INSERT INTO projects (
  portfolio_id,
  display_order,
  project_title,
  project_description,
  project_url,
  project_image_url,
  repo_url,
  tags
) VALUES
(
  @portfolio_id,
  1,
  'Portfolio Generator SaaS',
  'A multi-step portfolio builder with public sharing, login, preview, and export features.',
  'https://example.com/portfolio-generator',
  'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80',
  'https://github.com/nassim12121/portfolio_generator-',
  'PHP, MySQL, JavaScript, UI/UX'
),
(
  @portfolio_id,
  2,
  'E-Commerce Landing Page',
  'A conversion-focused landing page with product sections, testimonials, and checkout CTA.',
  'https://example.com/ecommerce-landing',
  'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1200&q=80',
  'https://github.com/nassim12121/ecommerce-landing',
  'HTML, CSS, SEO, Performance'
),
(
  @portfolio_id,
  3,
  'Business Dashboard UI',
  'A clean admin dashboard designed for analytics, project tracking, and client management.',
  'https://example.com/dashboard-ui',
  'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=80',
  'https://github.com/nassim12121/dashboard-ui',
  'JavaScript, Charts, Dashboard, UX'
);

INSERT INTO social_links (portfolio_id, platform_name, profile_url) VALUES
(@portfolio_id, 'github', 'https://github.com/nassim12121'),
(@portfolio_id, 'linkedin', 'https://www.linkedin.com/in/nassim-charaabi'),
(@portfolio_id, 'twitter', 'https://x.com/nassimcharaabi'),
(@portfolio_id, 'instagram', 'https://instagram.com/nassimcharaabi');

COMMIT;

/*
Test entry points after import:
- Guest search: guest.php?q=nassim-charaabi-freelance-portfolio
- Public portfolio: public_portfolio.php?slug=nassim-charaabi-freelance-portfolio
*/
