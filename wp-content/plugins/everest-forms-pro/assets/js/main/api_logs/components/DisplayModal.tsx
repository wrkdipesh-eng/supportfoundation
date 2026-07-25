import {
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalHeader,
	ModalOverlay,
	Text,
} from "@chakra-ui/react";
import React, { ReactNode } from "react";

const bgOverlay = () => (
	<ModalOverlay
		bg="blackAlpha.300"
		backdropFilter="blur(10px) hue-rotate(90deg)"
	/>
);

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
		>
			{showBgOverlay && bgOverlay()}
			<ModalOverlay />
			<ModalContent>
				<ModalHeader fontSize={"medium"}>{title || ""}</ModalHeader>
				<Text
					fontSize={"small"}
					fontWeight={500}
					my={1}
					textAlign={"center"}
				>
					{extraInfo && extraInfo}
				</Text>
				{showCloseOption && <ModalCloseButton />}
				<ModalBody>{children}</ModalBody>
			</ModalContent>
		</Modal>
	);
};

export default React.memo(DisplayModal);
