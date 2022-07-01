<?php 
	namespace App\Service;

	use Doctrine\ORM\Tools\Pagination\Paginator;
	use Doctrine\ORM\EntityManagerInterface;
	use Doctrine\ORM\Query;

	class PaginatorService{

		// propiedades 
		private $resultsPerPage;
		private $em;
		private $actualPage = 1;
		private $totalResults = 0;

		// CONSTRUCTOR
		public function __construct(int $resultsPerPage, EntityManagerInterface $em){
			$this->resultsPerPage = $resultsPerPage;
			$this->em = $em;
		}

		public function setResultsPerPage(int $resultsPerPage){
			$this->resultsPerPage = $resultsPerPage;
		}

		public function getActualPage():int{
			return $this->actualPage;
		}

		public function getTotalResults():int{
			return $this->totalResults;
		}

		public function getTotalPages():int{
			return ceil($this->totalResults / $this->resultsPerPage);
		}


		// MÉTODOS

		public function paginate(Query $dql, $page = 1):Paginator{

			$paginator = new Paginator($dql);

			$paginator->getQuery()
				->setFirstResult($this->resultsPerPage * ($page - 1))
				->setMAxResults($this->resultsPerPage);

			$this->paginaActual = $page;
			$this->totalResults = $paginator->count();
			
			return $paginator;
		}



		public function findAll(
			string $entity,
			int $page = 1,
			string $orderField = 'id',
			String $order = 'DESC'
		):Paginator{
			$consulta = $this->em->createQuery(
				"SELECT p FROM $entity p 
				ORDER BY p.$orderField $order");

			return $this->paginate($consulta, $page);
		}




	}