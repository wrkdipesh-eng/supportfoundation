import {
	Button,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalFooter,
	ModalHeader,
	ModalOverlay,
	Text,
} from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";
import React, { ReactNode } from "react";

interface ModalProps {
	title?: string;
	isOpen: boolean;
	onClose: () => void;
	children?: ReactNode;
	showCloseOption?: boolean;
	closeOnOverlayClick?: boolean;
	size?:
		| string
		| {
				base?: string;
				sm?: string;
				md?: string;
				lg?: string;
				xl?: string;
				full?: string;
		  };
	showBgOverlay?: boolean;
	extraInfo?: ReactNode;
	applyPadding?: boolean;
}

const DisplayModal: React.FC<ModalProps> = ({
	isOpen,
	onClose,
	title,
	children,
	showCloseOption = true,
	closeOnOverlayClick = true,
	size = { base: "full", md: "xl", lg: "2xl", xl: "4xl", "2xl": "5xl" },
	showBgOverlay = true,
	extraInfo,
	applyPadding = true,
}) => {
	return (
		<Modal
			size={size}
			isOpen={isOpen}
			onClose={onClose}
			closeOnOverlayClick={closeOnOverlayClick}
			motionPreset="none"
		>
			{showBgOverlay && (
				<ModalOverlay bg="blackAlpha.400" backdropFilter="blur(4px)" />
			)}
			<ModalContent>
				{title && (
					<ModalHeader
						fontSize="16px"
						fontWeight="600"
						color="gray.800"
						borderBottom="1px solid"
						borderColor="gray.200"
						pb="16px"
					>
						{title}
					</ModalHeader>
				)}
				{extraInfo && (
					<Text
						fontSize="small"
						fontWeight={500}
						my={1}
						textAlign="center"
					>
						{extraInfo}
					</Text>
				)}
				{showCloseOption && <ModalCloseButton />}
				<ModalBody py="24px">{children}</ModalBody>
				<ModalFooter
					borderTop="1px solid"
					borderColor="gray.200"
					pt="12px"
					pb="16px"
				>
					<Button
						size="sm"
						variant="outline"
						onClick={onClose}
						borderRadius="4px"
						colorScheme="primary"
					>
						{__("Close", "everest-forms-pro")}
					</Button>
				</ModalFooter>
			</ModalContent>
		</Modal>
	);
};

export default React.memo(DisplayModal);
