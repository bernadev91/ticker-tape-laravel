<?php

namespace App\Http\Controllers;

use App\Models\Calculation;
use App\Services\ExpressionEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Calculations
 *
 * Endpoints for evaluating expressions and managing
 * per-session calculation history for the CalcTek calculator.
 */
class CalculationController extends Controller
{
    /**
     * Resolve the current calculator session token from the request.
     *
     * The token is expected in the `X-Calculator-Session` header, but can
     * also be provided as a `session_token` query or body parameter.
     *
     * @param  Request  $request
     * @return string
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *         When the session token is missing.
     */
    private function getSessionToken(Request $request): string
    {
        $token = $request->header('X-Calculator-Session')
            ?? $request->query('session_token')
            ?? (string) $request->input('session_token', '');

        if ($token === '') {
            abort(400, 'Missing calculator session token.');
        }

        return $token;
    }

    /**
     * List all calculations for the current session.
     *
     * Returns calculations in reverse-chronological order (newest first)
     * for the session identified by the `X-Calculator-Session` header.
     *
     * @header X-Calculator-Session string required
     *     The token identifying this calculator session.
     *
     * @response 200 {
     *   "calculations": [
     *     {
     *       "id": 1,
     *       "session_token": "uuid-or-random-string",
     *       "expression": "1+2*3",
     *       "result": "7",
     *       "had_error": false,
     *       "error_message": null,
     *       "created_at": "2026-02-25T07:45:00.000000Z",
     *       "updated_at": "2026-02-25T07:45:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $sessionToken = $this->getSessionToken($request);

        $calculations = Calculation::where('session_token', $sessionToken)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'calculations' => $calculations,
        ]);
    }

    /**
     * Evaluate an expression and store the calculation.
     *
     * The expression is evaluated on the server using a math-expression
     * library and the result (or error) is persisted for this session.
     *
     * @header X-Calculator-Session string required
     *     The token identifying this calculator session.
     *
     * @bodyParam expression string required
     *     The math expression to evaluate. Example: "sqrt((((9*9)/12)+(13-4))*2)^2".
     *
     * @response 201 {
     *   "calculation": {
     *     "id": 1,
     *     "session_token": "uuid-or-random-string",
     *     "expression": "1+2*3",
     *     "result": "7",
     *     "had_error": false,
     *     "error_message": null,
     *     "created_at": "2026-02-25T07:45:00.000000Z",
     *     "updated_at": "2026-02-25T07:45:00.000000Z"
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'expression' => ['required', 'string', 'max:255'],
        ]);

        $sessionToken = $this->getSessionToken($request);

        $evaluator = new ExpressionEvaluator();
        $evaluation = $evaluator->evaluate($data['expression']);

        $calculation = Calculation::create([
            'session_token' => $sessionToken,
            'expression' => $evaluation['expression'],
            'result' => $evaluation['result'],
            'had_error' => $evaluation['had_error'],
            'error_message' => $evaluation['error_message'],
        ]);

        return response()->json([
            'calculation' => $calculation,
        ], 201);
    }

    /**
     * Delete a single calculation for the current session.
     *
     * Only deletes the calculation if it belongs to the session identified
     * by the `X-Calculator-Session` header.
     *
     * @header X-Calculator-Session string required
     *     The token identifying this calculator session.
     *
     * @urlParam calculation int required
     *     The ID of the calculation to delete.
     *
     * @response 204 {}
     */
    public function destroy(Request $request, int $calculation): JsonResponse
    {
        $sessionToken = $this->getSessionToken($request);

        $model = Calculation::whereKey($calculation)
            ->where('session_token', $sessionToken)
            ->firstOrFail();

        $model->delete();

        return response()->json([], 204);
    }

    /**
     * Clear all calculations for the current session.
     *
     * Deletes every calculation record associated with the current
     * `X-Calculator-Session` token.
     *
     * @header X-Calculator-Session string required
     *     The token identifying this calculator session.
     *
     * @response 204 {}
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $sessionToken = $this->getSessionToken($request);

        Calculation::where('session_token', $sessionToken)->delete();

        return response()->json([], 204);
    }
}

