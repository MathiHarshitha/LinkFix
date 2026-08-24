<?php
/**
 * Aggregates scan-index data into the numbers shown on the Dashboard
 * and Statistics admin pages. Everything here is derived from the
 * local elm_links table — no visitor tracking, no third-party calls.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Statistics;

use ELM\Database\LinksRepository;
use ELM\Scanner\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StatisticsService {

	/** @var LinksRepository */
	private $repository;

	/** @var Scanner */
	private $scanner;

	public function __construct( Scanner $scanner ) {
		$this->repository = new LinksRepository();
		$this->scanner    = $scanner;
	}

	public function get_summary(): array {
		$summary = array(
			'total_external_links' => $this->repository->count_total_links(),
			'unique_domains'       => $this->repository->count_unique_domains(),
			'content_with_links'   => $this->repository->count_content_with_links(),
			'top_domains'          => $this->repository->get_top_domains( 20 ),
			'rel_distribution'     => $this->repository->get_rel_distribution(),
			'last_scan'            => $this->scanner->get_last_scan_summary(),
		);

		/**
		 * Filters the aggregated statistics payload, letting Pro append
		 * link-health counts, historical deltas, etc.
		 *
		 * @param array $summary
		 */
		return apply_filters( 'elm_statistics_summary', $summary );
	}
}
