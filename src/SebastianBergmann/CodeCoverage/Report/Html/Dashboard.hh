<?hh // strict

/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCoverage\Report\Html;

use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\TextTemplate\TemplateFactory;
use SebastianBergmann\TextTemplate\Template;
use Zynga\CodeBase\V1\Code\Code_Class;
use Zynga\CodeBase\V1\Code\Code_Method;

/**
 * Renders the dashboard for a directory node.
 */
class Dashboard extends Renderer {
  /**
   * @param DirectoryNode $node
   * @param string        $file
   */
  public function render(DirectoryNode $node, string $file): void {
    $classes = $node->getClassesAndTraits();

    $template =
      TemplateFactory::get($this->templatePath.'dashboard.html', '{{', '}}');

    $templateVariables = $this->getCommonTemplateVariables($node);
    $templateVariablesMap = $templateVariables->asMap();

    $baseLink = $node->getId().'/';
    $complexity = $this->complexity($classes, $baseLink);
    $coverageDistribution = $this->coverageDistribution($classes);
    $insufficientCoverage = $this->insufficientCoverage($classes, $baseLink);
    $projectRisks = $this->projectRisks($classes, $baseLink);

    $templateVariablesMap->set(
      'insufficient_coverage_classes',
      $insufficientCoverage['class'],
    );
    $templateVariablesMap->set(
      'insufficient_coverage_methods',
      $insufficientCoverage['method'],
    );
    $templateVariablesMap->set(
      'project_risks_classes',
      $projectRisks['class'],
    );
    $templateVariablesMap->set(
      'project_risks_methods',
      $projectRisks['method'],
    );
    $templateVariablesMap->set('complexity_class', $complexity['class']);
    $templateVariablesMap->set('complexity_method', $complexity['method']);
    $templateVariablesMap->set(
      'class_coverage_distribution',
      $coverageDistribution['class'],
    );
    $templateVariablesMap->set(
      'method_coverage_distribution',
      $coverageDistribution['method'],
    );

    $template->renderTo($file, $templateVariablesMap);

  }

  /**
   * Returns the data for the Class/Method Complexity charts.
   *
   * @param array  $classes
   * @param string $baseLink
   *
   * @return array
   */
  protected function complexity(
    Map<string, Code_Class> $classes,
    string $baseLink,
  ): array<string, string> {
    $result = ['class' => [], 'method' => []];

    foreach ($classes as $className => $class) {
      foreach ($class->methods as $methodName => $method) {
        if ($className != '*') {
          $methodName = $className.'::'.$methodName;
        }

        $result['method'][] = [
          $method->coverage,
          $method->getCcn(),
          sprintf(
            '<a href="%s">%s</a>',
            str_replace($baseLink, '', $method->link),
            $methodName,
          ),
        ];
      }

      $result['class'][] = [
        $class->coverage,
        $class->getCcn(),
        sprintf(
          '<a href="%s">%s</a>',
          str_replace($baseLink, '', $class->link),
          $className,
        ),
      ];
    }

    return [
      'class' => json_encode($result['class']),
      'method' => json_encode($result['method']),
    ];

  }

  /**
   * Returns the data for the Class / Method Coverage Distribution chart.
   *
   * @param array $classes
   *
   * @return array
   */
  protected function coverageDistribution(
    Map<string, Code_Class> $classes,
  ): array<string, array<string, float>> {
    $result = [
      'class' => [
        '0%' => 0,
        '0-10%' => 0,
        '10-20%' => 0,
        '20-30%' => 0,
        '30-40%' => 0,
        '40-50%' => 0,
        '50-60%' => 0,
        '60-70%' => 0,
        '70-80%' => 0,
        '80-90%' => 0,
        '90-100%' => 0,
        '100%' => 0,
      ],
      'method' => [
        '0%' => 0,
        '0-10%' => 0,
        '10-20%' => 0,
        '20-30%' => 0,
        '30-40%' => 0,
        '40-50%' => 0,
        '50-60%' => 0,
        '60-70%' => 0,
        '70-80%' => 0,
        '80-90%' => 0,
        '90-100%' => 0,
        '100%' => 0,
      ],
    ];

    foreach ($classes as $class) {
      foreach ($class->methods as $methodName => $method) {
        if ($method->coverage == 0) {
          $result['method']['0%']++;
        } else if ($method->coverage == 100) {
          $result['method']['100%']++;
        } else {
          $key = floor($method->coverage / 10) * 10;
          $key = $key.'-'.($key + 10).'%';
          $result['method'][$key]++;
        }
      }

      if ($class->coverage == 0) {
        $result['class']['0%']++;
      } else if ($class->coverage == 100) {
        $result['class']['100%']++;
      } else {
        $key = floor($class->coverage / 10) * 10;
        $key = $key.'-'.($key + 10).'%';
        $result['class'][$key]++;
      }
    }

    return [
      'class' => json_encode(array_values($result['class'])),
      'method' => json_encode(array_values($result['method'])),
    ];
  }

  /**
   * Returns the classes / methods with insufficient coverage.
   *
   * @param array  $classes
   * @param string $baseLink
   *
   * @return array
   */
  protected function insufficientCoverage(
    Map<string, Code_Class> $classes,
    string $baseLink,
  ): array<string, string> {
    $leastTestedClasses = [];
    $leastTestedMethods = [];
    $result = ['class' => '', 'method' => ''];

    foreach ($classes as $className => $class) {
      foreach ($class->methods as $methodName => $method) {
        if ($method->coverage < $this->highLowerBound) {
          if ($className != '*') {
            $key = $className.'::'.$methodName;
          } else {
            $key = $methodName;
          }

          $leastTestedMethods[$key] = $method->coverage;
        }
      }

      if ($class->coverage < $this->highLowerBound) {
        $leastTestedClasses[$className] = $class->coverage;
      }
    }

    asort($leastTestedClasses);
    asort($leastTestedMethods);

    foreach ($leastTestedClasses as $className => $coverage) {
      $classLink = '';
      $class = $classes->get($className);
      if ($class instanceof Code_Class) {
        $classLink = $class->link;
      }
      $result['class'] .=
        sprintf(
          '       <tr><td><a href="%s">%s</a></td><td class="text-right">%d%%</td></tr>'.
          "\n",
          str_replace($baseLink, '', $classLink),
          $className,
          $coverage,
        );
    }

    foreach ($leastTestedMethods as $methodName => $coverage) {

      list($class, $method) = explode('::', $methodName);

      $classObj = $classes->get($class);
      $methodLink = '';

      if ($classObj instanceof Code_Class) {
        $methodObj = $classObj->methods->get($method);
        if ($methodObj instanceof Code_Method) {
          $methodLink = $methodObj->link;
        }
      }
      $result['method'] .=
        sprintf(
          '       <tr><td><a href="%s"><abbr title="%s">%s</abbr></a></td><td class="text-right">%d%%</td></tr>'.
          "\n",
          str_replace($baseLink, '', $methodLink),
          $methodName,
          $method,
          $coverage,
        );
    }

    return $result;
  }

  /**
   * Returns the project risks according to the CRAP index.
   *
   * @param array  $classes
   * @param string $baseLink
   *
   * @return array
   */
  protected function projectRisks(
    Map<string, Code_Class> $classes,
    string $baseLink,
  ): array<string, string> {
    $classRisks = [];
    $methodRisks = [];
    $result = ['class' => '', 'method' => ''];

    foreach ($classes as $className => $class) {
      foreach ($class->methods as $methodName => $method) {
        if ($method->coverage < $this->highLowerBound &&
            $method->getCcn() > 1) {
          if ($className != '*') {
            $key = $className.'::'.$methodName;
          } else {
            $key = $methodName;
          }

          $methodRisks[$key] = $method->getCrap();
        }
      }

      if ($class->coverage < $this->highLowerBound &&
          $class->getCcn() > $class->methods->count()) {
        $classRisks[$className] = $class->getCrap();
      }
    }

    arsort($classRisks);
    arsort($methodRisks);

    foreach ($classRisks as $className => $crap) {

      $classObj = $classes->get($className);

      $classLink = '';
      if ($classObj instanceof Code_Class) {
        $classLink = $classObj->link;
      }

      $result['class'] .=
        sprintf(
          '       <tr><td><a href="%s">%s</a></td><td class="text-right">%d</td></tr>'.
          "\n",
          str_replace($baseLink, '', $classLink),
          $className,
          $crap,
        );
    }

    foreach ($methodRisks as $methodName => $crap) {
      list($class, $method) = explode('::', $methodName);

      $classObj = $classes->get($class);

      $methodLink = '';
      if ($classObj instanceof Code_Class) {
        $methodObj = $classObj->methods->get($method);
        if ($methodObj instanceof Code_Method) {
          $methodLink = $methodObj->link;
        }
      }

      $result['method'] .=
        sprintf(
          '       <tr><td><a href="%s"><abbr title="%s">%s</abbr></a></td><td class="text-right">%d</td></tr>'.
          "\n",
          str_replace($baseLink, '', $methodLink),
          $methodName,
          $method,
          $crap,
        );
    }

    return $result;
  }

  /*
   protected function getActiveBreadcrumb(AbstractNode $node): string {
   return sprintf(
   '        <li><a href="index.html">%s</a></li>'.
   "\n".
   '        <li class="active">(Dashboard)</li>'.
   "\n",
   $node->getName(),
   );
   }
   */
}
