<?php

declare(strict_types=1);

namespace App\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Webmozart\Assert\Assert;

final class ProductNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'sylius_product_normalizer_already_called';

    public function __construct(
        private ProductVariantResolverInterface $defaultProductVariantResolver,
        private IriConverterInterface $iriConverter,
    ) {
    }

    public function normalize($object, $format = null, array $context = [])
    {
        Assert::isInstanceOf($object, ProductInterface::class);
        Assert::keyNotExists($context, self::ALREADY_CALLED);

        $context[self::ALREADY_CALLED] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        $defaultVariant = $this->defaultProductVariantResolver->getVariant($object);
        $data['defaultVariant'] = $defaultVariant === null ? null : $this->iriConverter->getIriFromResource(
            $defaultVariant
        );

        return $data;
    }

    public function supportsNormalization($data, $format = null, $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof ProductInterface && $this->isShopOperation($context);
    }

    private function isShopOperation(array $context): bool
    {
        if (isset($context['item_operation_name'])) {
            return \str_starts_with($context['item_operation_name'], 'shop_get');
        }
        if (isset($context['collection_operation_name'])) {
            return \str_starts_with($context['collection_operation_name'], 'shop_get');
        }

        return false;
    }
}
