<?php

use Base\Developer as BaseDeveloper;


/**
 * Skeleton subclass for representing a row from the 'developer' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Developer extends BaseDeveloper
{
    public function avatar()
    {
        if ($this->getAvatarUrl() == "") {
            return "/img/no-avatar.png";
        } else {
            return $this->getAvatarUrl();
        }
    }

    public function gatherStats()
    {
        $db_projects = $this->getProjects(true);
        $langs = array();
        $projects = array();
        foreach ($db_projects as $project) {
            $projects[] = array(
                "language" => $project->language,
                "name" => $project->name,
                "commits" => $project->countCommits(),
                "pr_acceptance_rate" => intval($project->pr_acceptance_rate),
                "stars" => intval($project->stars),
                "forks" => intval($project->forks),
                "faq_count" => count($project->getQuestions()),
                "total_pull_requests" => $project->getTotalPullRequests()
            );
        }
        $stats = array(
            "owned_projects" => $projects,
            "contributed_projects" => $this->getMyContributions()
        );

        return $stats;
    }
}
