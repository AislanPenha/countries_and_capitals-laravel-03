<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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
            'correct_answers'  => 0,
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
        $answers[] = $quiz[$current_question]['correct_answers'];
        
        shuffle($answers);
        return view('game')->with([
            'country' => $quiz[$current_question]['country'],
            'totalQuestions' => $total_questions,
            'currentQuestion' => $current_question,
            'answers' => $answers
        ]);

    }

    public function answer($enc_answer) {
        try{
            $answer = Crypt::decryptString($enc_answer);
        } catch(\Exception $e){
            return redirect()->route('game');
        } 

        // game logic
        $quiz = session('quiz');
        $current_question = session('current_question') - 1;
        $correct_answer = $quiz[$current_question]['correct_answers'];
        $correct_answers = session('correct_answers');
        $wrong_answers = session('wrong_answers');

        if($answer == $correct_answer) {
            $correct_answers++;
            $quiz[$current_question]['correct'] = true;
        } else {
            $wrong_answers++;
            $quiz[$current_question]['correct'] = false;
        }

        // update session
        session()->put([
            'quiz' => $quiz,
            //'total_questions'  => $total_questions,
            //'current_question' => 1,
            'correct_answers'  => $correct_answers,
            'wrong_answers'    => $wrong_answers,
        ]);

        // prepare data to show the correct answers
        $data = [
            'country' => $quiz[$current_question]['country'],
            'correct_answer' => $correct_answer,
            'choice_answer' => $answer,
            'currentQuestion'  => $current_question,
            'totalQuestions' => session('total_questions')
        ];

        return view('answer_result')->with($data);
    }

    public function nextQuestion() {
        $current_question = session('current_question');
        $total_questions = session('total_questions');

        // check if the game is over
        if($current_question < $total_questions) {
            $current_question++;
            session()->put('current_question' , $current_question);
            return redirect()->route('game');
        } else {
            // game over
            return redirect()->route('show_results');
        }
    }

    public function showResults(): View{
        $total_questions = session('total_questions');
        $correct_answers  = session('correct_answers');
        return view('final_results')->with([
            'correct_answers' => $correct_answers,
            'wrong_answers' => session('wrong_answers'),
            'total_questions' => $total_questions,
            'percentage' => round($correct_answers / $total_questions*100, 2)
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
            $question['correct_answers'] = $this->app_data[$index]['capital'];

            // worng answers
            $other_captials = array_column($this->app_data, 'capital');

            // remove correct answer
            $other_captials = array_diff($other_captials, [$question['correct_answers']]);

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
