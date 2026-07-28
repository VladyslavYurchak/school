<?php

namespace Database\Seeders;

use App\Models\Testing\Test;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EnglishPlacementTestSeeder extends Seeder
{
    private const SLUG = 'english-placement-2026-v1';

    public function run(): void
    {
        DB::transaction(function (): void {
            $existing = Test::query()
                ->where('slug', self::SLUG)
                ->with(['questions.options'])
                ->withCount('sections')
                ->first();

            if ($existing) {
                $optionCount = $existing->questions->sum(
                    fn ($question) => $question->options->count()
                );
                $correctOptionCount = $existing->questions->sum(
                    fn ($question) => $question->options->where('is_correct', true)->count()
                );

                if (
                    $existing->sections_count !== 6
                    || $existing->questions->count() !== 50
                    || $optionCount !== 200
                    || $correctOptionCount !== 50
                ) {
                    throw new RuntimeException(
                        'English placement test exists but its seeded structure is incomplete.'
                    );
                }

                return;
            }

            $test = Test::create([
                'title' => 'English Placement Test',
                'slug' => self::SLUG,
                'language_code' => 'en',
                'description' => 'Розширений тест для орієнтовного визначення рівня англійської від A1 до C1.',
                'intro_text' => 'Тест містить 50 питань із граматики, лексики та читання. Оберіть одну найкращу відповідь. Якщо не впевнені, не користуйтеся перекладачем: так результат буде точнішим. Орієнтовний час проходження — 35–45 хвилин.',
                'weight' => 1,
                'max_score' => 50,
                'is_active' => true,
                'is_public' => true,
                'randomize_questions' => false,
                'show_result_immediately' => true,
                'time_limit_minutes' => 50,
                'sort_order' => 10,
            ]);

            $questionNumber = 1;

            foreach ($this->sections() as $sectionOrder => $sectionData) {
                $questions = $sectionData['questions'];
                unset($sectionData['questions']);

                $section = $test->sections()->create([
                    ...$sectionData,
                    'sort_order' => $sectionOrder + 1,
                    'is_active' => true,
                    'media_type' => 'none',
                ]);

                foreach ($questions as $questionOrder => $questionData) {
                    $options = $questionData['options'];
                    unset($questionData['options']);

                    $question = $section->questions()->create([
                        ...$questionData,
                        'test_id' => $test->id,
                        'title' => sprintf('EP26-%03d', $questionNumber),
                        'type' => 'single_choice',
                        'default_correct_points' => 1,
                        'default_incorrect_points' => 0,
                        'is_required' => true,
                        'is_active' => true,
                        'sort_order' => $questionOrder + 1,
                    ]);

                    foreach ($options as $optionOrder => $option) {
                        $question->options()->create([
                            'option_text' => $option['text'],
                            'option_value' => chr(65 + $optionOrder),
                            'is_correct' => $option['correct'] ?? false,
                            'points' => ($option['correct'] ?? false) ? 1 : 0,
                            'explanation' => $option['explanation'] ?? null,
                            'sort_order' => $optionOrder + 1,
                        ]);
                    }

                    $questionNumber++;
                }
            }

            $test->recalculateMaxScore();
        });
    }

    private function sections(): array
    {
        return [
            [
                'title' => 'Grammar: Foundations',
                'description' => 'Оберіть варіант, який найкраще доповнює речення.',
                'instruction_text' => 'У кожному питанні є лише одна правильна відповідь.',
                'type' => 'grammar',
                'questions' => [
                    $this->question('A1', 'My sister ___ in Kyiv.', [
                        'live',
                        $this->correct('lives', 'У Present Simple після he/she/it до дієслова додаємо -s.'),
                        'is live',
                        'living',
                    ]),
                    $this->question('A1', 'We ___ coffee every morning.', [
                        'drinks',
                        'are drink',
                        $this->correct('drink', 'З підметом we у Present Simple використовуємо початкову форму дієслова.'),
                        'drinking',
                    ]),
                    $this->question('A1', '___ you like music?', [
                        'Are',
                        $this->correct('Do', 'Питання в Present Simple з you починається з do.'),
                        'Does',
                        'Is',
                    ]),
                    $this->question('A1', 'There ___ two books on the desk.', [
                        'is',
                        'be',
                        'has',
                        $this->correct('are', 'З іменником у множині використовуємо there are.'),
                    ]),
                    $this->question('A1', 'Yesterday I ___ at home and watched a film.', [
                        $this->correct('stayed', 'Yesterday вказує на Past Simple.'),
                        'stay',
                        'have stayed',
                        'am staying',
                    ]),
                    $this->question('A1', 'I cannot talk now. I ___ dinner.', [
                        'cook',
                        'cooked',
                        $this->correct('am cooking', 'Дія відбувається прямо зараз, тому потрібен Present Continuous.'),
                        'have cooked',
                    ]),
                    $this->question('A2', 'She has lived in this town ___ 2022.', [
                        'for',
                        'from',
                        $this->correct('since', 'Since використовується з конкретним моментом початку.'),
                        'during',
                    ]),
                    $this->question('A2', 'If it rains tomorrow, we ___ at home.', [
                        'stayed',
                        $this->correct('will stay', 'У First Conditional: if + Present Simple, will + infinitive.'),
                        'would stay',
                        'stay yesterday',
                    ]),
                    $this->question('A2', 'This suitcase is ___ than mine.', [
                        'more heavy',
                        'heaviest',
                        'the heavier',
                        $this->correct('heavier', 'Порівняльна форма heavy — heavier.'),
                    ]),
                    $this->question('A2', 'I ___ that film twice.', [
                        'see',
                        'saw since',
                        $this->correct('have seen', 'Досвід без зазначеного завершеного часу виражаємо Present Perfect.'),
                        'am seeing',
                    ]),
                    $this->question('A2', 'When I arrived, they ___ dinner.', [
                        'have',
                        $this->correct('were having', 'Тривала дія була в процесі, коли сталася інша дія.'),
                        'had',
                        'are having',
                    ]),
                    $this->question('A2', 'You ___ wear a uniform here; it is optional.', [
                        'must not',
                        'cannot',
                        'should not',
                        $this->correct('do not have to', 'Do not have to означає відсутність необхідності.'),
                    ]),
                    $this->question('B1', 'If I ___ more free time, I would learn Italian.', [
                        'have',
                        $this->correct('had', 'У Second Conditional після if використовуємо Past Simple.'),
                        'will have',
                        'would have',
                    ]),
                    $this->question('B1', 'The package ___ yesterday afternoon.', [
                        'delivered',
                        'has delivered',
                        $this->correct('was delivered', 'Потрібен пасивний стан у Past Simple.'),
                        'is delivering',
                    ]),
                    $this->question('B1', 'He asked me where I ___.', [
                        'do live',
                        'am living?',
                        'did I live',
                        $this->correct('lived', 'У непрямому питанні зберігається прямий порядок слів.'),
                    ]),
                ],
            ],
            [
                'title' => 'Grammar: Intermediate and Advanced',
                'description' => 'У цій частині конструкції поступово стають складнішими.',
                'instruction_text' => 'Оберіть граматично й стилістично найкращий варіант.',
                'type' => 'grammar',
                'questions' => [
                    $this->question('B1', 'I wish I ___ speak French more confidently.', [
                        'can',
                        'will',
                        $this->correct('could', 'Після wish для теперішньої нереальної ситуації використовуємо форму минулого часу.'),
                        'should',
                    ]),
                    $this->question('B1', 'By the time we got to the cinema, the film ___.', [
                        'started',
                        $this->correct('had started', 'Дія завершилася раніше за іншу минулу дію.'),
                        'has started',
                        'was starting tomorrow',
                    ]),
                    $this->question('B1', 'She suggested ___ the earlier train.', [
                        'to take',
                        'take',
                        'that taking',
                        $this->correct('taking', 'Після suggest можна використовувати герундій.'),
                    ]),
                    $this->question('B2', 'If you ___ me earlier, I could have helped.', [
                        'told',
                        'would tell',
                        $this->correct('had told', 'У Third Conditional після if використовуємо Past Perfect.'),
                        'have told',
                    ]),
                    $this->question('B2', 'The final report ___ by Friday.', [
                        'must complete',
                        $this->correct('must be completed', 'Після modal verb у пасиві: modal + be + past participle.'),
                        'must have completing',
                        'is must completed',
                    ]),
                    $this->question('B2', 'Hardly ___ down when the phone rang.', [
                        'I had sat',
                        'did I sit',
                        'I sat',
                        $this->correct('had I sat', 'Після hardly на початку речення потрібна інверсія в Past Perfect.'),
                    ]),
                    $this->question('B2', 'Despite ___ tired, Maya continued working.', [
                        'she was',
                        'to be',
                        $this->correct('being', 'Після despite використовуємо іменник або герундій.'),
                        'was',
                    ]),
                    $this->question('B2', 'The person ___ laptop was stolen called the police.', [
                        'who',
                        $this->correct('whose', 'Whose виражає належність.'),
                        'which',
                        'whom is',
                    ]),
                    $this->question('B2', 'I would rather you ___ this matter to anyone.', [
                        'do not mention',
                        'will not mention',
                        'not mentioning',
                        $this->correct('did not mention', 'Після would rather + person використовуємо Past Simple.'),
                    ]),
                    $this->question('C1', 'Not until the meeting ended ___ the scale of the problem.', [
                        'we understood',
                        'we did understand',
                        $this->correct('did we understand', 'Після негативного виразу на початку речення потрібна інверсія.'),
                        'had we understanding',
                    ]),
                    $this->question('C1', 'The proposal, ___ initially controversial, was eventually approved.', [
                        'despite',
                        $this->correct('though', 'Though може вводити скорочену допустову конструкцію.'),
                        'because',
                        'whereas of',
                    ]),
                    $this->question('C1', 'Were the company ___ more in training, staff turnover might fall.', [
                        'invested',
                        'investing',
                        'invests',
                        $this->correct('to invest', 'Were + subject + to-infinitive — формальна інверсія умовного речення.'),
                    ]),
                    $this->question('C1', 'It is high time the authorities ___ action.', [
                        'take',
                        'have taken',
                        $this->correct('took', 'Після it is high time використовується Past Simple.'),
                        'will take',
                    ]),
                    $this->question('C1', 'No sooner ___ the announcement than reporters began asking questions.', [
                        'she made',
                        'did she make',
                        $this->correct('had she made', 'No sooner на початку вимагає інверсії в Past Perfect.'),
                        'she had making',
                    ]),
                    $this->question('C1', 'He denied ___ the confidential information.', [
                        'to disclose',
                        'disclose',
                        'to have disclose',
                        $this->correct('having disclosed', 'Після deny використовується герундій; perfect gerund підкреслює попередню дію.'),
                    ]),
                ],
            ],
            [
                'title' => 'Vocabulary in Context',
                'description' => 'Оберіть слово або вираз, який природно підходить до ситуації.',
                'instruction_text' => 'Звертайте увагу не лише на переклад, а й на контекст речення.',
                'type' => 'vocabulary',
                'questions' => [
                    $this->question('A1', 'Could I ___ your pen for a minute?', [
                        'lend',
                        $this->correct('borrow', 'Borrow означає взяти щось у когось на певний час.'),
                        'give',
                        'bring',
                    ]),
                    $this->question('A1', 'The bus was very ___, so we had to stand.', [
                        'empty',
                        'quietly',
                        $this->correct('crowded', 'Crowded означає, що в місці багато людей.'),
                        'cheap',
                    ]),
                    $this->question('A2', 'I have a dentist’s ___ at three o’clock.', [
                        $this->correct('appointment', 'Appointment — домовлена зустріч із лікарем або спеціалістом.'),
                        'invitation',
                        'lesson',
                        'queue',
                    ]),
                    $this->question('A2', 'We have ___ milk. Could you buy some?', [
                        'taken after',
                        $this->correct('run out of', 'Run out of означає, що запас чогось закінчився.'),
                        'looked for',
                        'put away',
                    ]),
                    $this->question('B1', 'Nina was ___ to accept the job because it required frequent travel.', [
                        'eagerly',
                        'capable',
                        $this->correct('reluctant', 'Reluctant означає неохочий або такий, що вагається.'),
                        'ordinary',
                    ]),
                    $this->question('B1', 'The speaker gave a very ___ explanation, so most people supported the plan.', [
                        'convincing',
                        'conveniently',
                        'sensible of',
                        'patient',
                    ], 0, 'Convincing означає переконливий.'),
                    $this->question('B2', 'The original plan was too expensive, so the team looked for a more ___ alternative.', [
                        'scarce',
                        $this->correct('feasible', 'Feasible означає практично здійсненний.'),
                        'inevitable',
                        'temporary of',
                    ]),
                    $this->question('B2', 'Do not ___ how long the visa process may take.', [
                        'undertake',
                        'overcome',
                        $this->correct('underestimate', 'Underestimate означає оцінити щось нижче від реального значення.'),
                        'undergo',
                    ]),
                    $this->question('C1', 'The editor was ___, checking every reference and punctuation mark.', [
                        'spontaneous',
                        $this->correct('meticulous', 'Meticulous означає надзвичайно уважний до деталей.'),
                        'indifferent',
                        'tentative',
                    ]),
                    $this->question('C1', 'Constant interruptions can be ___ to employees’ ability to concentrate.', [
                        'complementary',
                        'eligible',
                        'subordinate',
                        $this->correct('detrimental', 'Detrimental означає шкідливий або такий, що має негативний вплив.'),
                    ]),
                ],
            ],
            [
                'title' => 'Reading: Lena’s New Job',
                'description' => "Lena works in the gift shop of a city museum from Tuesday to Saturday. She starts at nine o’clock and usually cycles to work, but she takes the bus when it rains. She began the job six months ago because she wanted to save money for a photography course.\n\nLena enjoys meeting visitors from different countries. Weekends are busy, while weekday mornings are often quiet. During quiet periods, her manager allows her to read about the museum’s exhibitions so that she can answer visitors’ questions.",
                'instruction_text' => 'Прочитайте текст і дайте відповіді на чотири питання.',
                'type' => 'reading',
                'questions' => [
                    $this->question('A1', 'Where does Lena work?', [
                        'At a photography school',
                        'At a bus station',
                        $this->correct('In a museum gift shop', 'Це прямо зазначено в першому реченні.'),
                        'In a restaurant',
                    ]),
                    $this->question('A1', 'How does Lena usually travel to work?', [
                        $this->correct('By bicycle', 'У тексті сказано that she usually cycles to work.'),
                        'By bus',
                        'By train',
                        'On foot',
                    ]),
                    $this->question('A2', 'Why did Lena begin the job?', [
                        'To meet tourists',
                        $this->correct('To save for a photography course', 'Це зазначено наприкінці першого абзацу.'),
                        'To become a museum manager',
                        'To learn to drive',
                    ]),
                    $this->question('A2', 'What does Lena do during quiet periods?', [
                        'She goes home early',
                        'She takes photographs',
                        'She closes the shop',
                        $this->correct('She reads about the exhibitions', 'Менеджер дозволяє їй читати про виставки.'),
                    ]),
                ],
            ],
            [
                'title' => 'Reading: The Repair Café',
                'description' => "Once a month, a community centre hosts a repair café where volunteers help local residents fix broken household items. Visitors bring lamps, small appliances, clothes and bicycles. Instead of simply handing an item to a volunteer, they are encouraged to watch the repair and learn how it is done.\n\nThe project began as a way to reduce waste, but it has also become a popular social event. Only twenty people attended the first session; now places must be booked in advance. Not every object can be saved, particularly when replacement parts are unavailable. Nevertheless, the organisers say the project’s real success lies in giving people practical skills and confidence, rather than in the number of objects repaired.",
                'instruction_text' => 'Прочитайте текст і оберіть найточнішу відповідь.',
                'type' => 'reading',
                'questions' => [
                    $this->question('B1', 'What are visitors encouraged to do at the repair café?', [
                        'Leave their items and return later',
                        $this->correct('Observe the repair and learn from it', 'Відвідувачів заохочують дивитися і навчатися.'),
                        'Buy replacement items',
                        'Pay volunteers for private lessons',
                    ]),
                    $this->question('B1', 'Why must places now be booked in advance?', [
                        'The centre has moved',
                        'Repairs have become more expensive',
                        $this->correct('The event has become popular', 'Кількість відвідувачів суттєво зросла.'),
                        'Only bicycles are accepted',
                    ]),
                    $this->question('B2', 'Why can some objects not be repaired?', [
                        'Volunteers refuse difficult jobs',
                        'Visitors do not stay to help',
                        'The sessions are too short',
                        $this->correct('Necessary parts may not be available', 'Текст прямо згадує unavailable replacement parts.'),
                    ]),
                    $this->question('B2', 'How do the organisers mainly judge the project’s success?', [
                        'By the money it earns',
                        $this->correct('By the skills and confidence people gain', 'Для організаторів навчання важливіше за кількість ремонтів.'),
                        'By the number of volunteers',
                        'By how quickly objects are repaired',
                    ]),
                ],
            ],
            [
                'title' => 'Reading: Rethinking Remote Work',
                'description' => "When a technology company introduced remote work, managers expected productivity either to rise dramatically or to collapse. Neither prediction proved accurate: output remained broadly stable. A less visible issue emerged, however. Established teams adapted easily, while recently hired employees found it harder to acquire the informal knowledge normally absorbed through everyday conversations.\n\nThe company responded with a hybrid policy built around two shared office days. These were not intended primarily for monitoring performance, but for mentoring, collaborative planning and maintaining professional relationships. The policy disappointed advocates of complete flexibility, yet employee surveys suggested that most staff valued autonomy and purposeful face-to-face contact rather than treating them as mutually exclusive.",
                'instruction_text' => 'Прочитайте уривок. Питання перевіряють розуміння авторської думки та висновків.',
                'type' => 'reading',
                'questions' => [
                    $this->question('C1', 'What was the main reason for introducing shared office days?', [
                        'To measure individual productivity more closely',
                        'To reduce the company’s office costs',
                        $this->correct('To support knowledge sharing and professional relationships', 'Офісні дні призначені для mentoring, planning and relationships.'),
                        'To reverse the remote-work policy completely',
                    ]),
                    $this->question('C1', 'Which conclusion is best supported by the passage?', [
                        'Remote work always harms productivity',
                        'New employees prefer being monitored in person',
                        'Complete flexibility is the only policy employees value',
                        $this->correct('Autonomy and useful in-person contact can complement each other', 'Фінальне речення прямо заперечує, що ці переваги взаємовиключні.'),
                    ]),
                ],
            ],
        ];
    }

    private function question(
        string $level,
        string $text,
        array $options,
        ?int $correctIndex = null,
        ?string $explanation = null
    ): array {
        if ($correctIndex !== null) {
            $options[$correctIndex] = $this->correct((string) $options[$correctIndex], $explanation);
        }

        return [
            'difficulty_level' => $level,
            'question_text' => $text,
            'options' => array_map(
                fn (string|array $option) => is_array($option)
                    ? $option
                    : ['text' => $option, 'correct' => false],
                $options
            ),
        ];
    }

    private function correct(string $text, ?string $explanation = null): array
    {
        return [
            'text' => $text,
            'correct' => true,
            'explanation' => $explanation,
        ];
    }
}
