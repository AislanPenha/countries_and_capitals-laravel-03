<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MainController extends Controller
{
    private $app_data;

    public function __construct()
    {
        // load app_data.php file from app folder
        $this->app_data = require(app_path('app_data.php'));
    }

    public function startGame(): View
    {
        return view('home');
    }

    public function prepareGame(Request $request)
    {
        // validate request
        $request->validate(
            [
                'total_questions' => 'required|integer|min:3|max:30'
            ],
            [
                'total_questions.required' => 'O número de questões é obrigatório',
                'total_questions.integer'  => 'O número de questões tem que ser um valor inteiro',
                'total_questions.min'      => 'No mínimo :min questões',
                'total_questions.max'      => 'No máximo :max questões',
            ]
        );
        
        // get total questions
        $total_questions = intval($request->input('total_questions'));
        
        // prepare all the quiz structure
        $quiz = $this->prepareQuiz($total_questions);

       
        // sotre the quiz in session
        session()->put([
            'quiz' => $quiz,
            'total_questions'  => $total_questions,
            'current_question' => 1,
            'correct_answer'  => 0,
            'wrong_answers'    => 0,
        ]);

        return redirect()->route('game');
    }

    public function showData() {
        return response()->json($this->app_data);
    }

    public function game(): View 
    {
        $quiz = session('quiz');
        $total_questions = session('total_questions');
        $current_question = session('current_question') - 1;

        // prepare answers to show in view
        $answers = $quiz[$current_question]['wrong_answers'];
        $answers[] = $quiz[$current_question]['correct_answer'];
        
        shuffle($answers);
        return view('game')->with([
            'country' => $quiz[$current_question]['country'],
            'totalQuestions' => $total_questions,
            'currentQuestion' => $current_question,
            'answers' => $answers
        ]);

    }
    private function prepareQuiz($total_questions): array {
        $questions = [];
        $total_countries = count($this->app_data);

        // create countries index for unique questions
        $indexes = range(0, $total_countries - 1);
        shuffle($indexes);
        $indexes = array_slice($indexes, 0, $total_questions);

        // create array of questions
        $question_number = 1;
        foreach($indexes as $index) {
            $question['question_number'] = $question_number++;
            $question['country'] = $this->app_data[$index]['country'];
            $question['correct_answer'] = $this->app_data[$index]['capital'];

            // worng answers
            $other_captials = array_column($this->app_data, 'capital');
            
            // remove correct answer
            $other_captials = array_diff($other_captials, [$question['correct_answer']]);

            // shuffle the wrong answer
            shuffle($other_captials);
            $question['wrong_answers'] = array_slice($other_captials, 0, 3);

            // store answer result
            $question['correct'] = null;

            $questions[] = $question;
        }
        return $questions;
    }
}
