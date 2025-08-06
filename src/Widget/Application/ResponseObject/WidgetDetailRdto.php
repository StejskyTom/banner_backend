<?php

declare(strict_types=1);

namespace App\Widget\Application\ResponseObject;

use App\Entity\Widget;

final readonly class WidgetDetailRdto
{
    public Widget $id;
    public string $title;

    public function __construct(Widget $widget)
    {
        $this->id = $widget;
        $this->title = $widget->getTitle();
    }
}
