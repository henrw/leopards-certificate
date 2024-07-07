CREATE TABLE learners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name TEXT
);

CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name TEXT
);

CREATE TABLE completion (
    learner_id INT,
    course_id INT,
    completion_date DATE NOT NULL DEFAULT CURDATE(),
    FOREIGN KEY (learner_id) REFERENCES learners(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Insert learners
INSERT INTO learners (name) VALUES ('Henry');
INSERT INTO learners (name) VALUES ('Monica');
INSERT INTO learners (name) VALUES ('Yvette');
INSERT INTO learners (name) VALUES ('Della');
INSERT INTO learners (name) VALUES ('Tracy');
INSERT INTO learners (name) VALUES ('Cody');

-- Insert courses
INSERT INTO courses (name) VALUES ('Adaptation, Crowdsourcing, and Persuasion');
INSERT INTO courses (name) VALUES ('Data-driven Knowledge Tracing to Improve Learning Outcomes');
INSERT INTO courses (name) VALUES ('Designing Online Collaboration Tools');
INSERT INTO courses (name) VALUES ('Evidence-Based Backward Design');
INSERT INTO courses (name) VALUES ('Foster Active Learning');
INSERT INTO courses (name) VALUES ('Foundations for Online Tool Design');
INSERT INTO courses (name) VALUES ('Introduction to Learning Engineering');
INSERT INTO courses (name) VALUES ('Introduction to Personalized Online Learning');
INSERT INTO courses (name) VALUES ('Quantitative and Experimental Methods for Designing and Evaluating Learning');
INSERT INTO courses (name) VALUES ('Techniques for Active Enriched Learning');
INSERT INTO courses (name) VALUES ('Uncovering Implicit Knowledge with Cognitive Task Analysis');
INSERT INTO courses (name) VALUES ('UX Design for Effective Instruction');

-- Example insert into completion
INSERT INTO completion (learner_id, course_id) VALUES (1, 1);