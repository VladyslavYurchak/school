<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\LessonTest;
use App\Models\LessonTestOption;
use Illuminate\Support\Facades\Log;

class TestFormComponent extends Component
{
    public $lesson_id;
    public $question;
    public $options = [];
    public $is_multiple_choice = false;

    public $existingQuestions = [];

    public function mount($lesson_id)
    {
        $this->lesson_id = $lesson_id;
        $this->loadQuestions();
    }

    public function loadQuestions()
    {
        $this->existingQuestions = LessonTest::where('lesson_id', $this->lesson_id)
            ->with('options')
            ->get();
    }

    public function addQuestion()
    {
        $test = LessonTest::create([
            'lesson_id' => $this->lesson_id,
            'question' => $this->question,
            'is_multiple_choice' => $this->is_multiple_choice,
        ]);

        foreach ($this->options as $option) {
            LessonTestOption::create([
                'lesson_test_id' => $test->id,
                'option_text' => $option['text'],
                'is_correct' => $option['is_correct'] ?? false,
            ]);
        }

        $this->resetForm();
        $this->loadQuestions();
    }

    public function resetForm()
    {
        $this->question = '';
        $this->options = [];
        $this->is_multiple_choice = false;
    }

    public function render()
    {
        return view('livewire.test-form-component');
    }
}
