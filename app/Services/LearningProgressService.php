<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Subscription;
use App\Models\Video;
use App\Models\VideoProgress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LearningProgressService
{
    /**
     * Minimum video completion percentage required
     * before the video is considered completed.
     */
    private const VIDEO_COMPLETION_REQUIRED = 90;

    /*
    |--------------------------------------------------------------------------
    | Subscription
    |--------------------------------------------------------------------------
    */

    /**
     * Get the student's current active and approved subscription.
     */
    public function getActiveSubscription(int $studentId): ?Subscription
    {
        return Subscription::query()
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->with('subscriptionCode')
            ->latest('id')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Lesson Access
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether a lesson is unlocked for the student.
     *
     * The first lesson is unlocked automatically.
     *
     * Every following lesson requires the previous lesson to be
     * completed and its exam to be passed.
     */
    public function isLessonUnlocked(
        int $studentId,
        int $subscriptionId,
        Lesson $lesson
    ): bool {
        $previousLesson = $this->getPreviousLesson($lesson);

        if (!$previousLesson) {
            return true;
        }

        return $this->isLessonCompleted(
            $studentId,
            $subscriptionId,
            $previousLesson
        ) && $this->hasPassedExam(
            $studentId,
            $subscriptionId,
            $previousLesson
        );
    }

    /**
     * Get the previous lesson according to section/lesson ordering.
     *
     * Lessons are ordered by section sort_order first, then lesson sort_order.
     */
    public function getPreviousLesson(Lesson $lesson): ?Lesson
    {
        $section = $lesson->section;

        if (!$section) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Previous lesson in the same section
        |--------------------------------------------------------------------------
        */

        $previousLesson = Lesson::query()
            ->where('section_id', $lesson->section_id)
            ->where('sort_order', '<', $lesson->sort_order)
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->first();

        if ($previousLesson) {
            return $previousLesson;
        }

        /*
        |--------------------------------------------------------------------------
        | Previous section
        |--------------------------------------------------------------------------
        */

        $previousSection = $section->newQuery()
            ->where('grade_id', $section->grade_id)
            ->where(function ($query) use ($section) {
                $query
                    ->where('sort_order', '<', $section->sort_order)
                    ->orWhere(function ($query) use ($section) {
                        $query
                            ->where('sort_order', $section->sort_order)
                            ->where('id', '<', $section->id);
                    });
            })
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->first();

        if (!$previousSection) {
            return null;
        }

        return $previousSection->lessons()
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Video Access
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether a video is unlocked.
     *
     * First video of the first unlocked lesson is available.
     * Every following video requires the previous video to be completed.
     */
    public function isVideoUnlocked(
        int $studentId,
        int $subscriptionId,
        Video $video
    ): bool {
        $lesson = $video->lesson;

        if (!$lesson) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Lesson itself must be unlocked first.
        |--------------------------------------------------------------------------
        */

        if (!$this->isLessonUnlocked(
            $studentId,
            $subscriptionId,
            $lesson
        )) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Previous video in same lesson
        |--------------------------------------------------------------------------
        */

        $previousVideo = Video::query()
            ->where('lesson_id', $video->lesson_id)
            ->where('is_active', true)
            ->where(function ($query) use ($video) {
                $query
                    ->where('sort_order', '<', $video->sort_order)
                    ->orWhere(function ($query) use ($video) {
                        $query
                            ->where('sort_order', $video->sort_order)
                            ->where('id', '<', $video->id);
                    });
            })
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | First video
        |--------------------------------------------------------------------------
        */

        if (!$previousVideo) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Previous video must be completed
        |--------------------------------------------------------------------------
        */

        return $this->isVideoCompleted(
            $studentId,
            $subscriptionId,
            $previousVideo
        );
    }

    /**
     * Determine whether a video is completed.
     */
    public function isVideoCompleted(
        int $studentId,
        int $subscriptionId,
        Video $video
    ): bool {
        return VideoProgress::query()
            ->where('student_id', $studentId)
            ->where('subscription_id', $subscriptionId)
            ->where('video_id', $video->id)
            ->where('is_completed', true)
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Video Progress
    |--------------------------------------------------------------------------
    */

    /**
     * Update video progress.
     */
    public function updateVideoProgress(
        int $studentId,
        int $subscriptionId,
        Video $video,
        int $currentPositionSeconds,
        int $watchedDurationSeconds,
        int $completionPercentage
    ): VideoProgress {
        /*
        |--------------------------------------------------------------------------
        | Do not allow progress on a locked video.
        |--------------------------------------------------------------------------
        */

        if (!$this->isVideoUnlocked(
            $studentId,
            $subscriptionId,
            $video
        )) {
            throw new \RuntimeException(
                'This video is locked. Complete the previous video first.'
            );
        }

        $completionPercentage = max(
            0,
            min(100, $completionPercentage)
        );

        $currentPositionSeconds = max(
            0,
            $currentPositionSeconds
        );

        $watchedDurationSeconds = max(
            0,
            $watchedDurationSeconds
        );

        $isCompleted = $completionPercentage >= self::VIDEO_COMPLETION_REQUIRED;

        return DB::transaction(function () use (
            $studentId,
            $subscriptionId,
            $video,
            $currentPositionSeconds,
            $watchedDurationSeconds,
            $completionPercentage,
            $isCompleted
        ) {
            $progress = VideoProgress::query()->firstOrNew([
                'student_id' => $studentId,
                'subscription_id' => $subscriptionId,
                'video_id' => $video->id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Never decrease progress
            |--------------------------------------------------------------------------
            */

            $progress->current_position_seconds = max(
                (int) $progress->current_position_seconds,
                $currentPositionSeconds
            );

            $progress->watched_duration_seconds = max(
                (int) $progress->watched_duration_seconds,
                $watchedDurationSeconds
            );

            $progress->completion_percentage = max(
                (int) $progress->completion_percentage,
                $completionPercentage
            );

            /*
            |--------------------------------------------------------------------------
            | Once completed, keep it completed.
            |--------------------------------------------------------------------------
            */

            if ($progress->is_completed) {
                $isCompleted = true;
            }

            $progress->is_completed = $isCompleted;

            if ($isCompleted && !$progress->completed_at) {
                $progress->completed_at = now();
            }

            $progress->save();

            /*
            |--------------------------------------------------------------------------
            | Update lesson progress
            |--------------------------------------------------------------------------
            */

            $this->refreshLessonProgress(
                $studentId,
                $subscriptionId,
                $video->lesson_id
            );

            return $progress->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Lesson Progress
    |--------------------------------------------------------------------------
    */

    /**
     * Refresh the progress status of a lesson.
     */
    public function refreshLessonProgress(
        int $studentId,
        int $subscriptionId,
        int $lessonId
    ): LessonProgress {
        $lesson = Lesson::query()
            ->with([
                'videos' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->findOrFail($lessonId);

        $videos = $lesson->videos;

        /*
        |--------------------------------------------------------------------------
        | No active videos
        |--------------------------------------------------------------------------
        */

        if ($videos->isEmpty()) {
            $status = 'not_started';
            $completedAt = null;
        } else {
            $completedVideos = VideoProgress::query()
                ->where('student_id', $studentId)
                ->where('subscription_id', $subscriptionId)
                ->whereIn('video_id', $videos->pluck('id'))
                ->where('is_completed', true)
                ->count();

            if ($completedVideos === 0) {
                $status = 'not_started';
                $completedAt = null;
            } elseif ($completedVideos < $videos->count()) {
                $status = 'in_progress';
                $completedAt = null;
            } else {
                $status = 'completed';
                $completedAt = now();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | First lesson is unlocked.
        | Other lessons depend on previous lesson + exam.
        |--------------------------------------------------------------------------
        */

        $lessonProgress = LessonProgress::query()->firstOrNew([
            'student_id' => $studentId,
            'subscription_id' => $subscriptionId,
            'lesson_id' => $lessonId,
        ]);

        $lessonProgress->status = $status;

        if ($status === 'completed') {
            $lessonProgress->completed_at ??= $completedAt;
        } else {
            $lessonProgress->completed_at = null;
        }

        $lessonProgress->save();

        return $lessonProgress->fresh();
    }

    /**
     * Determine whether all active videos in a lesson are completed.
     */
    public function isLessonCompleted(
        int $studentId,
        int $subscriptionId,
        Lesson $lesson
    ): bool {
        $videos = $lesson->videos()
            ->where('is_active', true)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | A lesson with no videos cannot be considered completed.
        |--------------------------------------------------------------------------
        */

        if ($videos->isEmpty()) {
            return false;
        }

        $completedCount = VideoProgress::query()
            ->where('student_id', $studentId)
            ->where('subscription_id', $subscriptionId)
            ->whereIn('video_id', $videos->pluck('id'))
            ->where('is_completed', true)
            ->count();

        return $completedCount === $videos->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Exam Access
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether the lesson exam is unlocked.
     *
     * All active videos of the lesson must be completed first.
     */
    public function isExamUnlocked(
        int $studentId,
        int $subscriptionId,
        Lesson $lesson
    ): bool {
        if (!$this->isLessonUnlocked(
            $studentId,
            $subscriptionId,
            $lesson
        )) {
            return false;
        }

        return $this->isLessonCompleted(
            $studentId,
            $subscriptionId,
            $lesson
        );
    }

    /**
     * Determine whether the student has passed the lesson exam.
     */
    public function hasPassedExam(
        int $studentId,
        int $subscriptionId,
        Lesson $lesson
    ): bool {
        $exam = $lesson->exam;

        if (!$exam || !$exam->is_active) {
            /*
            |--------------------------------------------------------------------------
            | If there is no active exam, there is no exam requirement.
            |--------------------------------------------------------------------------
            */
            return true;
        }

        return ExamAttempt::query()
            ->where('student_id', $studentId)
            ->where('subscription_id', $subscriptionId)
            ->where('exam_id', $exam->id)
            ->where('is_passed', true)
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Lesson Status
    |--------------------------------------------------------------------------
    */

    /**
     * Get the complete learning status for a lesson.
     */
    public function getLessonStatus(
        int $studentId,
        int $subscriptionId,
        Lesson $lesson
    ): array {
        $lessonUnlocked = $this->isLessonUnlocked(
            $studentId,
            $subscriptionId,
            $lesson
        );

        $lessonCompleted = $this->isLessonCompleted(
            $studentId,
            $subscriptionId,
            $lesson
        );

        $exam = $lesson->exam;

        $examUnlocked = $exam
            ? $this->isExamUnlocked(
                $studentId,
                $subscriptionId,
                $lesson
            )
            : false;

        $examPassed = $exam
            ? $this->hasPassedExam(
                $studentId,
                $subscriptionId,
                $lesson
            )
            : false;

        return [
            'lesson_id' => $lesson->id,
            'is_unlocked' => $lessonUnlocked,
            'is_completed' => $lessonCompleted,
            'exam' => $exam ? [
                'id' => $exam->id,
                'is_active' => (bool) $exam->is_active,
                'is_unlocked' => $examUnlocked,
                'is_passed' => $examPassed,
            ] : null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Video Status
    |--------------------------------------------------------------------------
    */

    /**
     * Get complete learning status for a video.
     */
    public function getVideoStatus(
        int $studentId,
        int $subscriptionId,
        Video $video
    ): array {
        $progress = VideoProgress::query()
            ->where('student_id', $studentId)
            ->where('subscription_id', $subscriptionId)
            ->where('video_id', $video->id)
            ->first();

        $isUnlocked = $this->isVideoUnlocked(
            $studentId,
            $subscriptionId,
            $video
        );

        return [
            'video_id' => $video->id,
            'is_unlocked' => $isUnlocked,
            'is_completed' => (bool) ($progress?->is_completed ?? false),
            'completion_percentage' => (int) ($progress?->completion_percentage ?? 0),
            'current_position_seconds' => (int) ($progress?->current_position_seconds ?? 0),
            'watched_duration_seconds' => (int) ($progress?->watched_duration_seconds ?? 0),
        ];
    }
}